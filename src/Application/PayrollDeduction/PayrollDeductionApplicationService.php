<?php

declare(strict_types=1);

namespace App\Application\PayrollDeduction;

// Application Layer
use App\Application\PayrollDeduction\Command\CreatePayrollDeductionCommand;
use App\Application\PayrollDeduction\Command\UpdatePayrollDeductionCommand;
use App\Application\PayrollDeduction\Dto\PayrollDeductionResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\PayrollDeduction\Entity\PayrollDeduction;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Domain\PayrollDeduction\Service\PayrollDeductionDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * PayrollDeduction Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class PayrollDeductionApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private PayrollDeductionRepositoryInterface $repository,
        private PayrollDeductionDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return PayrollDeduction::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): PayrollDeduction
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

    public function view(int $id): PayrollDeductionResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return PayrollDeductionResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreatePayrollDeductionCommand $command): PayrollDeductionResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $command->detailInfo ?? []
            )
            ->build();

        $name = sprintf('DED-%d-%s', $command->employeeId, $command->period);

        $data = PayrollDeduction::create(
            name: $name,
            employeeId: $command->employeeId,
            deductionTypeId: $command->deductionTypeId,
            attendanceReportId: $command->attendanceReportId,
            employeeDebtId: $command->employeeDebtId,
            employeeDebtInstallmentId: $command->employeeDebtInstallmentId,
            period: $command->period,
            amount: $command->amount,
            description: $command->description,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return PayrollDeductionResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdatePayrollDeductionCommand $command): PayrollDeductionResponse
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

        if (!empty($command->employeeId)) {
            $data->setEmployeeId($command->employeeId);
        }

        if (!empty($command->deductionTypeId)) {
            $data->setDeductionTypeId($command->deductionTypeId);
        }

        if ($command->attendanceReportId !== null) {
            $data->setAttendanceReportId($command->attendanceReportId);
        }

        if ($command->employeeDebtId !== null) {
            $data->setEmployeeDebtId($command->employeeDebtId);
        }

        if ($command->employeeDebtInstallmentId !== null) {
            $data->setEmployeeDebtInstallmentId($command->employeeDebtInstallmentId);
        }

        if (!empty($command->period)) {
            $data->setPeriod($command->period);
            $data->updateName(
                newName: sprintf('DED-%d-%s', $command->employeeId ?? $data->getEmployeeId(), $command->period)
            );
        }

        if ($command->amount !== null) {
            $data->setAmount($command->amount);
        }

        if ($command->description !== null) {
            $data->setDescription($command->description);
        }

        $data->applyStatus(
            newStatus: $newStatus
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return PayrollDeductionResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): PayrollDeductionResponse
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
            permission: 'payrolldeduction.delete',
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

        return PayrollDeductionResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): PayrollDeductionResponse
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
        
        return PayrollDeductionResponse::fromEntity(
            entity: $restoredData
        );
    }
}
