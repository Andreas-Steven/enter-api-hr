<?php

declare(strict_types=1);

namespace App\Application\EmployeeLeave;

// Application Layer
use App\Application\EmployeeLeave\Command\AdjustEmployeeLeaveCommand;
use App\Application\EmployeeLeave\Command\AssignEmployeeLeaveCommand;
use App\Application\EmployeeLeave\Command\CreateEmployeeLeaveCommand;
use App\Application\EmployeeLeave\Command\UpdateEmployeeLeaveCommand;
use App\Application\EmployeeLeave\Dto\EmployeeLeaveResponse;
use App\Application\EmployeeLeave\Dto\EmployeeLeaveResult;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\EmployeeLeave\Entity\EmployeeLeave;
use App\Domain\EmployeeLeave\Repository\EmployeeLeaveRepositoryInterface;
use App\Domain\EmployeeLeave\Service\EmployeeLeaveDomainService;
use App\Domain\Shared\Contract\DateTimeProviderInterface;
use App\Domain\Shared\Enums\AdjustmentType;
use App\Domain\Shared\Enums\AdjustmentReason;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Infrastructure Layer
use App\Infrastructure\Security\CurrentUser;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * EmployeeLeave Application Service (Mandor/Alur Kerja)
 *
 * Orchestrates use cases and coordinates domain & infrastructure. The create
 * use case supports both a single-employee leave quota and a grand-assign
 * that creates quotas for many employees at once (by branch, division,
 * employee type, or explicit employee IDs).
 */
final class EmployeeLeaveApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private EmployeeLeaveRepositoryInterface $repository,
        private EmployeeLeaveDomainService $domainService,
        private EmployeeLeaveDetailInfoFactory $leaveInfoFactory,
        private DateTimeProviderInterface $dateTime,
        private CurrentUser $currentUser,
    ) {
    }

    public function getResource(): string
    {
        return EmployeeLeave::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): EmployeeLeave
    {
        $data = $this->repository->findById(
            id: $id,
            status: $status
        );

        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }

        return $data;
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(
            criteria: $criteria
        );
    }

    public function view(int $id): EmployeeLeaveResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        return EmployeeLeaveResponse::fromEntity(
            entity: $data
        );
    }

    /**
     * Creates a single EmployeeLeave for one employee.
     *
     * The employee snapshot (id, name, type, position, division, branch) is
     * stored inside detail_info.employee so the leave record stays readable
     * even if the employee master data changes afterwards.
     */
    public function create(CreateEmployeeLeaveCommand $command): EmployeeLeaveResponse
    {
        $leaveInfo = $this->leaveInfoFactory->buildFromPrimitives(
            employeeId: $command->employeeId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $leaveInfo->toArray()
            )
            ->build();

        $data = EmployeeLeave::create(
            name: $command->name,
            leaveType: $command->leaveType,
            employeeId: $command->employeeId,
            periodStartDate: $command->periodStartDate,
            periodEndDate: $command->periodEndDate,
            initialAmount: $command->initialAmount,
            usedAmount: $command->usedAmount,
            leaveRecords: $command->leaveRecords,
            adjustmentRecords: [],
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo,
        );

        return EmployeeLeaveResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    /**
     * Grand-assign: creates one EmployeeLeave per active employee matching the
     * command's scope (branch_id, division_id, employee_type_id, or an
     * explicit list of employee_ids).
     *
     * Delegates to the domain service and returns an EmployeeLeaveResult
     * summary (created / skipped / errors / items).
     */
    public function assign(AssignEmployeeLeaveCommand $command): EmployeeLeaveResult
    {
        return $this->domainService->assignToEmployees($command);
    }

    public function update(int $id, UpdateEmployeeLeaveCommand $command): EmployeeLeaveResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null
        );

        $newStatus = ResourceStatus::tryFrom($command->status);

        $hasFieldChanges = $data->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus
        );

        if (!empty($command->name)) {
            $data->updateName(
                newName: $command->name
            );
        }

        if (!empty($newStatus)) {
            $data->applyStatus(
                newStatus: $newStatus
            );
        }

        if (!empty($command->leaveType)) {
            $data->updateLeaveType(
                newLeaveType: $command->leaveType
            );
        }

        if (!empty($command->employeeId)) {
            $data->updateEmployeeId(
                newEmployeeId: $command->employeeId
            );
        }

        if (!empty($command->periodStartDate)) {
            $data->updatePeriodStartDate(
                newPeriodStartDate: $command->periodStartDate
            );
        }

        if (!empty($command->periodEndDate)) {
            $data->updatePeriodEndDate(
                newPeriodEndDate: $command->periodEndDate
            );
        }

        if (!empty($command->initialAmount)) {
            $data->updateInitialAmount(
                newInitialAmount: $command->initialAmount
            );
        }

        if ($command->usedAmount !== null) {
            $data->updateUsedAmount(
                newUsedAmount: $command->usedAmount
            );
        }

        if ($command->leaveRecords !== null) {
            $data->updateLeaveRecords(
                newLeaveRecords: $command->leaveRecords
            );
        }

        // Build the detail_info payload. Refresh the employee snapshot
        // stored inside detail_info.employee using the new employee_id (or
        // the existing one as fallback) so it stays in sync with the record.
        $leaveInfo = $this->leaveInfoFactory->buildFromPrimitives(
            employeeId: $command->employeeId ?? $data->getEmployeeId(),
        );

        $detailInfoPayload = array_merge(
            $leaveInfo->toArray(),
            $command->detailInfo ?? [],
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $detailInfoPayload,
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return EmployeeLeaveResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    /**
     * Appends a single adjustment transaction to an existing EmployeeLeave
     * record and recalculates adjustment_amount.
     *
     * Used by PUT /v1/employee-leave/{id}/adjustment.
     */
    public function adjust(AdjustEmployeeLeaveCommand $command): EmployeeLeaveResponse
    {
        $data = $this->getEntityById(
            id: $command->id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null
        );

        $data->addAdjustment(
            amount: $command->amount,
            type: AdjustmentType::from($command->adjustmentType),
            reason: AdjustmentReason::from($command->adjustmentLeaveType),
            note: $command->note,
        );

        // Update detail_info change_log so the adjustment is auditable.
        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return EmployeeLeaveResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): EmployeeLeaveResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $lockVersion,
        );

        $this->domainService->guardPermission(
            id: $id,
            authorizer: $this->auth,
            permission: 'employee-leave.delete',
            resource: $this->getResource(),
        );

        $this->domainService->ensureDeletable(
            entity: $data,
            resource: $this->getResource(),
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return EmployeeLeaveResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): EmployeeLeaveResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: ResourceStatus::deleted()->value()
        );

        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: false,
            newStatus: ResourceStatus::restored()
        );

        $data->markAsRestored();

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        $restoredData = $this->repository->restore(
            id: $data->getId()
        );

        return EmployeeLeaveResponse::fromEntity(
            entity: $restoredData
        );
    }
}
