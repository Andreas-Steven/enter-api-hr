<?php

declare(strict_types=1);

namespace App\Application\Attendance;

// Application Layer
use App\Application\Attendance\Command\CreateAttendanceCommand;
use App\Application\Attendance\Command\UpdateAttendanceCommand;
use App\Application\Attendance\Dto\AttendanceShowRow;
use App\Application\Attendance\Dto\AttendanceResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\Attendance\Entity\Attendance;
use App\Domain\Attendance\Service\AttendanceDomainService;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Application Attendance Detail Info
use App\Application\Attendance\AttendanceDetailInfoFactory;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Attendance Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class AttendanceApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceRepositoryInterface $repository,
        private AttendanceDomainService $domainService,
        private AttendanceDetailInfoFactory $attendanceDetailInfoFactory,
    ) {
    }

    public function getResource(): string
    {
        return Attendance::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): Attendance
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

    public function view(int $id): AttendanceResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return AttendanceResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateAttendanceCommand $command): AttendanceResponse
    {
        $existingAttendance = $this->repository->findByDate(
            date: $command->date
        );
        
        $existingEntries = $existingAttendance?->getAttendance() ?? [];
        
        $enrichedAttendance = $this->domainService->prepareRecords(
            date: $command->date,
            entries: $command->attendance,
            existingEntries: $existingEntries
        );

        if ($existingAttendance) {
            // LOGIKA UPDATE (Data tanggal yang sama sudah ada)
            $existingAttendance->updateEntries(
                entries: $enrichedAttendance
            );

            $data = $this->repository->update(
                entity: $existingAttendance
            );
        } else {
            // LOGIKA INSERT DATA BARU
            // --- AWAL LOGIKA AUTO-COMPLETE ---
            $previousActive = $this->repository->findLatestActive();
            
            if ($previousActive && $previousActive->getDate() < $command->date) {
                // Tambahkan label Missed Checkout ke setiap item yang belum memiliki end time
                $oldEntries = $previousActive->getAttendance(); 

                // Panggil Domain Service untuk melakukan finalisasi (Missed Checkout, dll)
                $finalizedEntries = $this->domainService->finalizeClosingRecords(
                    entries: $oldEntries
                );

                // Update entitas dengan data yang sudah difinalisasi
                $previousActive->updateEntries(
                    entries: $finalizedEntries
                );

                // Set status completed
                $previousActive->applyStatus(
                    newStatus: ResourceStatus::completed()
                );
                
                // Simpan perubahan
                $this->repository->update(
                    entity: $previousActive
                );
            }
            // --- AKHIR LOGIKA AUTO-COMPLETE ---

            $detailInfoDTO = $this->attendanceDetailInfoFactory->buildFromPrimitives(
                dayoff: false,
                blacklist: [],
            );

            $detailInfo = $this->detailInfoFactory->create(
                detailInfo: $detailInfoDTO->toArray()
            )->build();

            $data = Attendance::create(
                date: $command->date,
                attendance: $enrichedAttendance,
                status: ResourceStatus::active(),
                detailInfo: $detailInfo
            );

            $data = $this->repository->insert(
                entity: $data
            );
        }

        return AttendanceResponse::fromEntity(
            entity: $data
        );
    }
 
    public function update(int $id, UpdateAttendanceCommand $command): AttendanceResponse
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

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return AttendanceResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): AttendanceResponse
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
            permission: 'attendance.delete',
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

        return AttendanceResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): AttendanceResponse
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
        
        return AttendanceResponse::fromEntity(
            entity: $restoredData
        );
    }

    /**
     * Attendance Report System
     */
    private function mapReportRows(array $rows): array
    {
        return array_map(
            static fn(array $row): AttendanceShowRow => AttendanceShowRow::fromArray($row),
            $rows
        );
    }

    public function reportData(int $employeeId, int $period): array
    {
        $criteria = new SearchCriteria(
            filter: [
                'period' => $period,
            ],
            page: 1,
            pageSize: 200,
            sortBy: 'date',
            sortDir: 'asc',
            allowedSort: [
                'date' => 'date',
                'period' => 'period',
            ]
        );

        $result = $this->list(criteria: $criteria);

        $reportRows = $this->mapReportRows($result->data);
        $filteredRows = [];

        foreach ($reportRows as $reportRow) {
            $filteredRow = $reportRow->filterAttendanceByEmployee($employeeId);

            if (!$filteredRow->hasAttendance()) {
                continue;
            }

            $filteredRows[] = $filteredRow->toArray();
        }

        return $filteredRows;
    }
}
