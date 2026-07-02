<?php

declare(strict_types=1);

namespace App\Application\AttendanceShift;

// Application Layer
use App\Application\AttendanceShift\Command\CreateAttendanceShiftCommand;
use App\Application\AttendanceShift\Command\UpdateAttendanceShiftCommand;
use App\Application\AttendanceShift\Dto\AttendanceShiftResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceShift\Entity\AttendanceShift;
use App\Domain\AttendanceShift\Repository\AttendanceShiftRepositoryInterface;
use App\Domain\AttendanceShift\Service\AttendanceShiftDomainService;
use App\Domain\AttendanceShift\ValueObject\DefaultWorkTime;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceShift Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class AttendanceShiftApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceShiftRepositoryInterface $repository,
        private AttendanceShiftDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return AttendanceShift::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): AttendanceShift
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

    public function view(int $id): AttendanceShiftResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return AttendanceShiftResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateAttendanceShiftCommand $command): AttendanceShiftResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $defaultWorkTime = DefaultWorkTime::fromTimeRange(
            startTime: $command->startTime,
            endTime: $command->endTime,
            breakMinutes: $command->breakMinutes,
            crossDay: $command->crossDay
        );

        $data = AttendanceShift::create(
            name: $command->name,
            startTime: $command->startTime,
            endTime: $command->endTime,
            breakMinutes: $command->breakMinutes,
            crossDay: $command->crossDay,
            defaultWorkTime: $defaultWorkTime,
            note: $command->note,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return AttendanceShiftResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateAttendanceShiftCommand $command): AttendanceShiftResponse
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

        if ($command->startTime !== null || $command->endTime !== null || $command->breakMinutes !== null || $command->crossDay !== null) {
            $data->updateTimes(
                startTime: $command->startTime ?? $data->getStartTime(),
                endTime: $command->endTime ?? $data->getEndTime(),
                breakMinutes: $command->breakMinutes ?? $data->getBreakMinutes(),
                crossDay: $command->crossDay ?? $data->getCrossDay()
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

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return AttendanceShiftResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): AttendanceShiftResponse
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
            permission: 'attendanceshift.delete',
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

        return AttendanceShiftResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): AttendanceShiftResponse
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
        
        return AttendanceShiftResponse::fromEntity(
            entity: $restoredData
        );
    }
}