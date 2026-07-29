<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebtTransaction;

// Application Layer
use App\Application\EmployeeDebtTransaction\Command\CreateEmployeeDebtTransactionCommand;
use App\Application\EmployeeDebtTransaction\Command\UpdateEmployeeDebtTransactionCommand;
use App\Application\EmployeeDebtTransaction\Dto\EmployeeDebtTransactionResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;
use App\Domain\EmployeeDebtTransaction\Repository\EmployeeDebtTransactionRepositoryInterface;
use App\Domain\EmployeeDebtTransaction\Service\EmployeeDebtTransactionDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * EmployeeDebtTransaction Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class EmployeeDebtTransactionApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private EmployeeDebtTransactionRepositoryInterface $repository,
        private EmployeeDebtTransactionDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return EmployeeDebtTransaction::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): EmployeeDebtTransaction
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

    public function view(int $id): EmployeeDebtTransactionResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateEmployeeDebtTransactionCommand $command): EmployeeDebtTransactionResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $command->detailInfo ?? []
            )
            ->build();

        $data = EmployeeDebtTransaction::create(
            name: sprintf('TRX-%d-%s-%s', $command->employeeDebtId, $command->type, $command->transactionDate),
            employeeDebtId: $command->employeeDebtId,
            employeeDebtInstallmentId: $command->employeeDebtInstallmentId,
            payrollDeductionId: $command->payrollDeductionId,
            type: $command->type,
            amount: $command->amount,
            transactionDate: $command->transactionDate,
            note: $command->note,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateEmployeeDebtTransactionCommand $command): EmployeeDebtTransactionResponse
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

        if ($command->employeeDebtId !== null) {
            $data->setEmployeeDebtId($command->employeeDebtId);
        }

        if ($command->employeeDebtInstallmentId !== null) {
            $data->setEmployeeDebtInstallmentId($command->employeeDebtInstallmentId);
        }

        if ($command->payrollDeductionId !== null) {
            $data->setPayrollDeductionId($command->payrollDeductionId);
        }

        if ($command->type !== null) {
            $data->setType($command->type);
        }

        if ($command->amount !== null) {
            $data->setAmount($command->amount);
        }

        if ($command->transactionDate !== null) {
            $data->setTransactionDate($command->transactionDate);
        }

        if ($command->note !== null) {
            $data->setNote($command->note);
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

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): EmployeeDebtTransactionResponse
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
            permission: 'employeedebttransaction.delete',
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

        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): EmployeeDebtTransactionResponse
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
        
        return EmployeeDebtTransactionResponse::fromEntity(
            entity: $restoredData
        );
    }
}
