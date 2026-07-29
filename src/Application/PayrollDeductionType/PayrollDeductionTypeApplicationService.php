<?php

declare(strict_types=1);

namespace App\Application\PayrollDeductionType;

// Application Layer
use App\Application\PayrollDeductionType\Command\CreatePayrollDeductionTypeCommand;
use App\Application\PayrollDeductionType\Command\UpdatePayrollDeductionTypeCommand;
use App\Application\PayrollDeductionType\Dto\PayrollDeductionTypeResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\PayrollDeductionType\Entity\PayrollDeductionType;
use App\Domain\PayrollDeductionType\Repository\PayrollDeductionTypeRepositoryInterface;
use App\Domain\PayrollDeductionType\Service\PayrollDeductionTypeDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * PayrollDeductionType Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class PayrollDeductionTypeApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private PayrollDeductionTypeRepositoryInterface $repository,
        private PayrollDeductionTypeDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return PayrollDeductionType::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): PayrollDeductionType
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

    public function view(int $id): PayrollDeductionTypeResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return PayrollDeductionTypeResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreatePayrollDeductionTypeCommand $command): PayrollDeductionTypeResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = PayrollDeductionType::create(
            name: $command->name,
            category: $command->category,
            createsDebt: $command->createsDebt,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return PayrollDeductionTypeResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdatePayrollDeductionTypeCommand $command): PayrollDeductionTypeResponse
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

        $data->updateName(
            newName: $command->name
        );

        $data->applyStatus(
            newStatus: $newStatus
        );

        if (!empty($command->category)) {
            $data->updateCategory(
                newCategory: $command->category
            );
        }

        if ($command->createsDebt !== null) {
            $data->updateCreatesDebt(
                newCreatesDebt: $command->createsDebt
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

        return PayrollDeductionTypeResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): PayrollDeductionTypeResponse
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
            permission: 'payroll-deduction-type.delete',
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

        return PayrollDeductionTypeResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): PayrollDeductionTypeResponse
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
        
        return PayrollDeductionTypeResponse::fromEntity(
            entity: $restoredData
        );
    }
}