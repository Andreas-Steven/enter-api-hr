<?php

declare(strict_types=1);

namespace App\Application\WorkPattern;

// Application Layer
use App\Application\WorkPattern\Command\CreateWorkPatternCommand;
use App\Application\WorkPattern\Command\UpdateWorkPatternCommand;
use App\Application\WorkPattern\Dto\WorkPatternResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\WorkPattern\Entity\WorkPattern;
use App\Domain\WorkPattern\Repository\WorkPatternRepositoryInterface;
use App\Domain\AttendanceShift\Repository\AttendanceShiftRepositoryInterface;
use App\Domain\WorkPattern\Service\WorkPatternDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\WorkPattern\ValueObject\WorkingDays;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * WorkPattern Application Service (Mandor/Alur Kerja)
 *
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class WorkPatternApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private WorkPatternRepositoryInterface $repository,
        private WorkPatternDomainService $domainService,
        private AttendanceShiftRepositoryInterface $attendanceShiftRepository
    ) {
    }

    public function getResource(): string
    {
        return WorkPattern::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): WorkPattern
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

    public function view(int $id): WorkPatternResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        return WorkPatternResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateWorkPatternCommand $command): WorkPatternResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = WorkPattern::create(
            name: $command->name,
            workingDays: WorkingDays::fromArray($command->workingDays, $this->attendanceShiftRepository),
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return WorkPatternResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateWorkPatternCommand $command): WorkPatternResponse
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

        // Only update working days if it's provided and not empty
        if ($command->workingDays !== null && is_array($command->workingDays) && count($command->workingDays) > 0) {
            $data->updateWorkingDays(
                workingDays: WorkingDays::fromArray($command->workingDays, $this->attendanceShiftRepository)
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

        return WorkPatternResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): WorkPatternResponse
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
            permission: 'work-pattern.delete',
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

        return WorkPatternResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): WorkPatternResponse
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

        return WorkPatternResponse::fromEntity(
            entity: $restoredData
        );
    }
}
