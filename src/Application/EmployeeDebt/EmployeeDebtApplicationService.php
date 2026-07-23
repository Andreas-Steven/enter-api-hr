<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebt;

// Application Layer
use App\Application\EmployeeDebt\Command\CreateEmployeeDebtCommand;
use App\Application\EmployeeDebt\Command\UpdateEmployeeDebtCommand;
use App\Application\EmployeeDebt\Dto\EmployeeDebtResponse;
use App\Application\EmployeeDebt\EmployeeDebtDetailInfoFactory;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\EmployeeDebt\Service\EmployeeDebtDomainService;
use App\Domain\EmployeeDebt\ValueObject\Name;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * EmployeeDebt Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class EmployeeDebtApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private EmployeeDebtDetailInfoFactory $debtInfoFactory,
        private EmployeeDebtRepositoryInterface $repository,
        private EmployeeDebtDomainService $domainService
        
    ) {
    }

    public function getResource(): string
    {
        return EmployeeDebt::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): EmployeeDebt
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

    public function view(int $id): EmployeeDebtResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return EmployeeDebtResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateEmployeeDebtCommand $command): EmployeeDebtResponse
    {
        $debtInfo = $this->debtInfoFactory->buildFromIds(
            employeeId: $command->employeeId,
            deductionTypeId: $command->deductionTypeId,
        );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $debtInfo->toArray()
            )
            ->build();

        $data = EmployeeDebt::create(
            name: Name::generate($command->employeeId)->toString(),
            employeeId: $command->employeeId,
            deductionTypeId: $command->deductionTypeId,
            amount: $command->amount,
            date: $command->date,
            note: $command->note,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return EmployeeDebtResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateEmployeeDebtCommand $command): EmployeeDebtResponse
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

        // $data->updateName(
        //     newName: $command->name
        // );

        if (!empty($command->employeeId)) {
            $data->setEmployeeId(
                employeeId: $command->employeeId
            );
        }

        if (!empty($command->deductionTypeId)) {
            $data->setDeductionTypeId(
                deductionTypeId: $command->deductionTypeId
            );
        }

        if (!empty($command->amount)) {
            $data->setAmount(
                amount: $command->amount
            );
        }

        if (!empty($command->date)) {
            $data->setDate(
                date: $command->date
            );
        }

        if ($command->note !== null) {
            $data->setNote(
                note: $command->note
            );
        }

        $data->applyStatus(
            newStatus: $newStatus
        );

        $debtInfo = $this->debtInfoFactory->buildFromIds(
            employeeId: $command->employeeId ?? $data->getEmployeeId(),
            deductionTypeId: $command->deductionTypeId ?? $data->getDeductionTypeId(),
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $debtInfo->toArray(),
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return EmployeeDebtResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): EmployeeDebtResponse
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
            permission: 'employee-debt.delete',
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

        return EmployeeDebtResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): EmployeeDebtResponse
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
        
        return EmployeeDebtResponse::fromEntity(
            entity: $restoredData
        );
    }
}