<?php

declare(strict_types=1);

namespace App\Application\EmployeeGrade;

// Application Layer
use App\Application\EmployeeGrade\Command\CreateEmployeeGradeCommand;
use App\Application\EmployeeGrade\Command\UpdateEmployeeGradeCommand;
use App\Application\EmployeeGrade\Dto\EmployeeGradeResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\EmployeeGrade\Entity\EmployeeGrade;
use App\Domain\EmployeeGrade\Repository\EmployeeGradeRepositoryInterface;
use App\Domain\EmployeeGrade\Service\EmployeeGradeDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * EmployeeGrade Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class EmployeeGradeApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private EmployeeGradeRepositoryInterface $repository,
        private EmployeeGradeDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return EmployeeGrade::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): EmployeeGrade
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

    public function view(int $id): EmployeeGradeResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return EmployeeGradeResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateEmployeeGradeCommand $command): EmployeeGradeResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = EmployeeGrade::create(
            name: $command->name,
            level: $command->level,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return EmployeeGradeResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateEmployeeGradeCommand $command): EmployeeGradeResponse
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

        if (!empty($command->level)) {
            $data->updateLevel(
                newLevel: $command->level
            );
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

        return EmployeeGradeResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): EmployeeGradeResponse
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
            permission: 'employee-grade.delete',
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

        return EmployeeGradeResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): EmployeeGradeResponse
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
        
        return EmployeeGradeResponse::fromEntity(
            entity: $restoredData
        );
    }
}
