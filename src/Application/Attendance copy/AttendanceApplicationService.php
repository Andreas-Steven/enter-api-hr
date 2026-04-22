<?php

declare(strict_types=1);

namespace App\Application\Attendance;

// Application Layer
use App\Application\Attendance\Command\CreateAttendanceCommand;
use App\Application\Attendance\Command\UpdateAttendanceCommand;
use App\Application\Attendance\Dto\AttendanceResponse;
use App\Application\Attendance\AttendanceDetailInfoFactory;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\Attendance\Entity\Attendance;
use App\Domain\Attendance\Service\AttendanceDomainService;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Domain\AttendanceDevice\Repository\AttendanceDeviceRepositoryInterface;
use App\Domain\Attendance\ValueObject\AttendancePeriod;
use App\Domain\Attendance\ValueObject\AttendanceRecord;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

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
        private EmployeeRepositoryInterface $employeeRepository,
        private AttendanceDeviceRepositoryInterface $deviceRepository,
        private AttendanceDetailInfoFactory $attendanceInfo,
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
        $systemDate = date('Y-m-d');
        $dateMismatch = null;

        // 1. VALIDASI TANGGAL
        if ($command->date !== $systemDate) {
            $dateMismatch = $command->date;
            $effectiveDate = $systemDate;
        } else {
            $effectiveDate = $command->date;
        }

        $attendance = $this->repository->findByDate($effectiveDate);
        if ($attendance === null) {
            // 2. CARI DAN TUTUP ATTENDANCE HARI SEBELUMNYA (Logic Porting dari Yii2)
            $previousAttendances = $this->repository->findActiveExceptDate($effectiveDate);
            foreach ($previousAttendances as $oldAttendance) {
                $oldAttendance->markMissedCheckouts();
                $oldAttendance->markComplete();
                $this->repository->update($oldAttendance);
            }

            // 3. BUAT ATTENDANCE BARU
            $attendanceInfo = $this->attendanceInfo->buildFromPrimitives(
                blackList: [],
                dayOff: false,
            );
            $detailInfo = $this->detailInfoFactory
                ->create(
                    detailInfo: []
                )
                ->build();

            $data = Attendance::create(
                date: $effectiveDate,
                period: AttendancePeriod::fromDate($effectiveDate)->value(),
                attendance: [],
                status: ResourceStatus::active(),
                detailInfo: $attendanceInfo->toArray()
            );

            $attendance = $this->repository->insert(
                entity: $data
            );
        }

        // 4. SYNC ATTENDANCE
        $this->domainService->handleCreate(
            attendance: $attendance,
            rawRecords: $command->attendance,
            date: $effectiveDate,
            dateMismatch: $dateMismatch
        );

        // 5. UPDATE BLACKLIST
        /**
         * Get blacklisted employees
         * TODO: 
         * [ ] Error: data start_time benar berubah menjadi kacau
         */
        // $blacklistData = $attendance->getBlacklistedEmployees(
        //     rawAttendanceData: $command->attendance 
        // );

        // $attendanceInfo = $this->attendanceInfo->buildFromPrimitives(
        //     blackList: $blacklistData,
        //     dayOff: $attendance->isDayOff(),
        // );

        // 6. UPDATE DETAIL INFO
        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $attendance->getDetailInfo(),
                // payload: $attendanceInfo->toArray(),
                payload: [],
            )
            ->build();

        $attendance->updateDetailInfo(
            detailInfo: $detailInfo
        );

        // 7. UPDATE ATTENDANCE
        return AttendanceResponse::fromEntity(
            entity: $this->repository->update(
                entity: $attendance
            )
        );
    }

    /*
    public function create(CreateAttendanceCommand $command): AttendanceResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = Attendance::create(
            name: $command->name,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return AttendanceResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
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
    */

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
}
