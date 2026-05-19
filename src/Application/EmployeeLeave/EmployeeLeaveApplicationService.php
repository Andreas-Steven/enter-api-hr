<?php

declare(strict_types=1);

namespace App\Application\EmployeeLeave;

// Application Layer
use App\Application\EmployeeLeave\Command\CreateEmployeeLeaveCommand;
use App\Application\EmployeeLeave\Command\UpdateEmployeeLeaveCommand;
use App\Application\EmployeeLeave\Dto\EmployeeLeaveResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\EmployeeLeave\Entity\EmployeeLeave;
use App\Domain\EmployeeLeave\Repository\EmployeeLeaveRepositoryInterface;
use App\Domain\EmployeeLeave\Service\EmployeeLeaveDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * EmployeeLeave Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class EmployeeLeaveApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private EmployeeLeaveRepositoryInterface $repository,
        private EmployeeLeaveDomainService $domainService
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

    public function create(CreateEmployeeLeaveCommand $command): EmployeeLeaveResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = EmployeeLeave::create(
            name: $command->name,
            leaveType: $command->leaveType,
            employeeId: $command->employeeId,
            periodStartDate: $command->periodStartDate,
            periodEndDate: $command->periodEndDate,
            totalAmount: $command->totalAmount,
            adjustmentAmount: $command->adjustmentAmount,
            usedAmount: $command->usedAmount,
            leaveRecords: $command->leaveRecords,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return EmployeeLeaveResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
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

        if (!empty($command->totalAmount)) {
            $data->updateTotalAmount(
                newTotalAmount: $command->totalAmount
            );
        }

        if ($command->adjustmentAmount !== null) {
            $data->updateAdjustmentAmount(
                newAdjustmentAmount: $command->adjustmentAmount
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

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
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
            permission: 'employeeleave.delete',
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
