<?php

declare(strict_types=1);

namespace App\Application\AttendancePeriod;

// Application Layer
use App\Application\AttendancePeriod\Command\CreateAttendancePeriodCommand;
use App\Application\AttendancePeriod\Command\UpdateAttendancePeriodCommand;
use App\Application\AttendancePeriod\Dto\AttendancePeriodResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendancePeriod\Entity\AttendancePeriod;
use App\Domain\AttendancePeriod\Repository\AttendancePeriodRepositoryInterface;
use App\Domain\AttendancePeriod\Service\AttendancePeriodDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendancePeriod Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class AttendancePeriodApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AttendancePeriodRepositoryInterface $repository,
        private AttendancePeriodDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return AttendancePeriod::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): AttendancePeriod
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

    public function view(int $id): AttendancePeriodResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return AttendancePeriodResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateAttendancePeriodCommand $command): array
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $generated = $this->domainService->generateYear(
            year: $command->year,
            status: ResourceStatus::active(),
            detailInfoFactory: fn () => $detailInfo,
        );

        return array_map(
            fn (AttendancePeriod $entity) => AttendancePeriodResponse::fromEntity($entity),
            $generated
        );
    }

    public function update(int $id, UpdateAttendancePeriodCommand $command): AttendancePeriodResponse
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

        $data->applyStatus(
            newStatus: $newStatus
        );

        $data->updateName(
            newName: $command->name
        );

        if (!empty($command->startDate)) {
            $data->updateStartDate(
                newStartDate: DateFormat::fromString($command->startDate)
            );
        }

        if (!empty($command->endDate)) {
            $data->updateEndDate(
                newEndDate: DateFormat::fromString($command->endDate)
            );
        }

        $this->domainService->ensurePeriodConsistency($data);

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return AttendancePeriodResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): AttendancePeriodResponse
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
            permission: 'attendance-period.delete',
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

        return AttendancePeriodResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): AttendancePeriodResponse
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
        
        return AttendancePeriodResponse::fromEntity(
            entity: $restoredData
        );
    }
}