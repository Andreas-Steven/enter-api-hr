<?php

declare(strict_types=1);

namespace App\Application\WorkTime;

// Application Layer
use App\Application\WorkTime\Command\CreateWorkTimeCommand;
use App\Application\WorkTime\Command\UpdateWorkTimeCommand;
use App\Application\WorkTime\Dto\WorkTimeResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\WorkTime\Entity\WorkTime;
use App\Domain\WorkTime\Repository\WorkTimeRepositoryInterface;
use App\Domain\WorkTime\Service\WorkTimeDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\WorkTime\ValueObject\WorkingDays;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * WorkTime Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class WorkTimeApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private WorkTimeRepositoryInterface $repository,
        private WorkTimeDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return WorkTime::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): WorkTime
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

    public function view(int $id): WorkTimeResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return WorkTimeResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateWorkTimeCommand $command): WorkTimeResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = WorkTime::create(
            name: $command->name,
            workingDays: WorkingDays::fromArray($command->workingDays),
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return WorkTimeResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateWorkTimeCommand $command): WorkTimeResponse
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

        $data->updateWorkingDays(
            workingDays: WorkingDays::fromArray($command->workingDays)
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

        return WorkTimeResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): WorkTimeResponse
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
            permission: 'work-time.delete',
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

        return WorkTimeResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): WorkTimeResponse
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
        
        return WorkTimeResponse::fromEntity(
            entity: $restoredData
        );
    }
}
