<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtInstallment;

// Application Layer
use App\Application\EmployeeDebtInstallment\Command\CreateEmployeeDebtInstallmentCommand;
use App\Application\EmployeeDebtInstallment\Command\UpdateEmployeeDebtInstallmentCommand;
use App\Application\EmployeeDebtInstallment\Dto\EmployeeDebtInstallmentResponse;
use App\Application\EmployeeDebtInstallment\EmployeeDebtInstallmentDetailInfoFactory;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;
use App\Domain\EmployeeDebtInstallment\Repository\EmployeeDebtInstallmentRepositoryInterface;
use App\Domain\EmployeeDebtInstallment\Service\EmployeeDebtInstallmentDomainService;
use App\Domain\EmployeeDebtInstallment\ValueObject\Name as InstallmentName;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * EmployeeDebtInstallment Application Service (Mandor/Alur Kerja)
 *
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class EmployeeDebtInstallmentApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private EmployeeDebtInstallmentDetailInfoFactory $installmentInfoFactory,
        private EmployeeDebtInstallmentRepositoryInterface $repository,
        private EmployeeDebtInstallmentDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return EmployeeDebtInstallment::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): EmployeeDebtInstallment
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

    public function view(int $id): EmployeeDebtInstallmentResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return EmployeeDebtInstallmentResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateEmployeeDebtInstallmentCommand $command): EmployeeDebtInstallmentResponse
    {
        $this->domainService->guardCreate(
            employeeDebtId: $command->employeeDebtId,
            sequence: $command->sequence,
            amount: $command->amount,
        );

        $installmentInfo = $this->installmentInfoFactory->buildFromIds(
            employeeDebtId: $command->employeeDebtId,
            payrollDeductionId: $command->payrollDeductionId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $installmentInfo->toArray()
            )
            ->build();

        $data = EmployeeDebtInstallment::create(
            name: InstallmentName::generate($command->employeeDebtId)->toString(),
            employeeDebtId: $command->employeeDebtId,
            payrollDeductionId: $command->payrollDeductionId,
            sequence: $command->sequence,
            dueDate: $command->dueDate,
            amount: $command->amount,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return EmployeeDebtInstallmentResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateEmployeeDebtInstallmentCommand $command): EmployeeDebtInstallmentResponse
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

        // Domain validation untuk perubahan sequence & employee_debt_id
        $this->domainService->guardUpdate(
            installment: $data,
            newSequence: $command->sequence,
            newEmployeeDebtId: $command->employeeDebtId,
        );

        if ($command->employeeDebtId !== null) {
            $data->setEmployeeDebtId($command->employeeDebtId);
        }

        if ($command->payrollDeductionId !== null) {
            $data->linkPayrollDeduction(
                payrollDeductionId: $command->payrollDeductionId
            );
        }

        if ($command->sequence !== null) {
            $data->setSequence($command->sequence);
        }

        if ($command->dueDate !== null) {
            $data->setDueDate($command->dueDate);
        }

        if ($command->amount !== null) {
            $data->setAmount($command->amount);
        }

        if ($command->paymentStatus !== null) {
            match ((int) $command->paymentStatus) {
                1 => (function () use ($data, $command): void {
                    $this->domainService->guardMarkAsPaid(
                        installment: $data,
                        payrollDeductionId: $command->payrollDeductionId,
                    );
                    $data->markAsPaid($command->payrollDeductionId);
                })(),
                2 => $data->markAsWaived(),
                default => null,
            };
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

        return EmployeeDebtInstallmentResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): EmployeeDebtInstallmentResponse
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
            permission: 'employeedebtinstallment.delete',
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

        return EmployeeDebtInstallmentResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): EmployeeDebtInstallmentResponse
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
        
        return EmployeeDebtInstallmentResponse::fromEntity(
            entity: $restoredData
        );
    }
}
