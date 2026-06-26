<?php

declare(strict_types=1);

namespace App\Application\AttendanceCalendar;

// Application Layer
use App\Application\AttendanceCalendar\Command\CreateAttendanceCalendarCommand;
use App\Application\AttendanceCalendar\Command\UpdateAttendanceCalendarCommand;
use App\Application\AttendanceCalendar\Dto\AttendanceCalendarResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendanceCalendar\Entity\AttendanceCalendar;
use App\Domain\AttendanceCalendar\Repository\AttendanceCalendarRepositoryInterface;
use App\Domain\AttendanceCalendar\Service\AttendanceCalendarDomainService;
use App\Domain\AttendanceCalendar\ValueObject\Scope;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceCalendar Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class AttendanceCalendarApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceCalendarRepositoryInterface $repository,
        private AttendanceCalendarDomainService $domainService,
    ) {
    }

    public function getResource(): string
    {
        return AttendanceCalendar::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): AttendanceCalendar
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

    public function view(int $id): AttendanceCalendarResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return AttendanceCalendarResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateAttendanceCalendarCommand $command): AttendanceCalendarResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $scopeData = $this->domainService->buildScopeFromPayload($command->scope);
        $this->domainService->ensureNoBranchConflict(
            date: $command->date,
            scopeData: $scopeData,
        );

        $data = AttendanceCalendar::create(
            name: $command->name,
            date: $command->date,
            holidayType: $command->holidayType,
            scope: Scope::fromArray($scopeData),
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo,
            note: $command->note,
            dayOff: $command->dayOff
        );

        return AttendanceCalendarResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateAttendanceCalendarCommand $command): AttendanceCalendarResponse
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
        
        if (!empty($command->date)) {
            $data->setDate(date: $command->date);
        }
        
        $scopeDataForValidation = !empty($command->scope)
            ? $this->domainService->buildScopeFromPayload($command->scope)
            : $data->getScope()->toArray();

        if (!empty($scopeDataForValidation)) {
            $effectiveDate = $command->date ?: $data->getDate();

            $this->domainService->ensureNoBranchConflict(
                date: $effectiveDate,
                scopeData: $scopeDataForValidation,
                excludeId: $data->getId(),
            );
        }

        if (!empty($command->holidayType)) {
            $data->setHolidayType(holidayType: $command->holidayType);
        }

        if (!empty($command->scope)) {
            $data->setScope(scope: Scope::fromArray($scopeDataForValidation));
        }

        if ($command->note !== null) {
            $data->setNote(note: $command->note);
        }

        if ($command->dayOff !== null) {
            $data->setDayOff(dayOff: $command->dayOff);
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

        return AttendanceCalendarResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): AttendanceCalendarResponse
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
            permission: 'attendance-calendar.delete',
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

        return AttendanceCalendarResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): AttendanceCalendarResponse
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
        
        return AttendanceCalendarResponse::fromEntity(
            entity: $restoredData
        );
    }

}
