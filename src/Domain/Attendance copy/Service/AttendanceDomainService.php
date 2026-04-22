<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Service;

use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\Attendance\Entity\Attendance;

use App\Domain\AttendanceDevice\Repository\AttendanceDeviceRepositoryInterface;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Attendance\ValueObject\AttendancePeriod;
use App\Domain\Shared\Enums\AttendanceStatus;

use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Attendance Domain Service
 * 
 * Pure business logic and domain rules
 */
final class AttendanceDomainService
{
    use DomainValidator;
    
    public function __construct(
        private DetailInfoFactory $detailInfoFactory,
        private AttendanceRepositoryInterface $repository,
        private EmployeeRepositoryInterface $employeeRepository,
        private AttendanceDeviceRepositoryInterface $attendanceDeviceRepository
    ) {}

    /**
     * Place Attendance-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     * 
     * Attendances:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */ 

    public function handleCreate(Attendance $attendance, array $rawRecords, string $date, ?string $dateMismatch = null): void
    {
        $processed = [];
        $attendanceData = $attendance->getAttendance();
        $existingRecords = array_column($attendanceData, null, 'employee_id');

        foreach ($rawRecords as $raw) {
            $employee = $this->employeeRepository->findById(
                id: $raw['employee_id'],
                status: ResourceStatus::active()->value()
            );

            if ($employee === null) { 
                throw new NotFoundException( 
                    translate: Message::create( 
                        key: 'resource.not_found', 
                        params: [ 
                            'resource' => 'Employee', 
                            'field' => 'ID', 
                            'value' => $raw['employee_id'] 
                        ] 
                    ) 
                );
            }
            
            $raw = $this->enrichWithEmployeeData($raw, $employee, $date);
            
            if ($raw['device']['code'] !== AttendanceStatus::DEVICE_APP) {
                $device = $this->attendanceDeviceRepository->findByCode(
                    code: $raw['device']['code'],
                    status: ResourceStatus::active()->value()
                );

                if ($device === null) { 
                    throw new NotFoundException(
                        translate: Message::create(
                            key: 'resource.not_found', 
                            params: [
                                'resource' => 'Device', 
                                'field' => 'Code', 
                                'value' => $raw['device']['code'] 
                            ],
                        ) 
                    ); 
                }    

                $raw = $this->enrichWithDeviceData($raw, $device);
            }

            if (isset($existingRecords[$raw['employee_id']])) {
                $raw = $this->mergeExisting(
                    $raw,
                    $existingRecords[$raw['employee_id']]
                );
            }

            $processed[] = $raw;
        }

        $isDayOff = (bool) ($attendance->getDetailInfo()->toArray()['dayoff'] ?? false);
        $attendance->syncRecords($processed, $isDayOff, $dateMismatch);
    }

    private function enrichWithEmployeeData(array $raw, $employee, string $date): array
    {
        $detailInfo = $employee->getDetailInfo()->toArray();

        $raw['employee_name'] = $employee->getName();
        $raw['division'] = $detailInfo['division'] ?? [];

        $workTime = $detailInfo['work_time'] ?? [];

        if (isset($workTime['working_days'])) {
            $dayIndex = (int) date('N', strtotime($date));
            $todaySchedule = $workTime['working_days'][$dayIndex] ?? [
                'home_time' => null,
                'entry_time' => null
            ];

            $workTime['working_days'] = [$dayIndex => $todaySchedule];
        }

        $raw['work_time'] = $workTime;

        return $raw;
    }

    private function enrichWithDeviceData(array $raw, $device): array
    {
        $deviceMaster = $device->toPersistance();

        $raw['device'] = [
            'id' => $deviceMaster['id'],
            'name' => $deviceMaster['name'],
            'code' => $deviceMaster['code'],
            'address' => $deviceMaster['address'],
            'ip_address' => $deviceMaster['ip_address'],
            'location' => $deviceMaster['location'],
        ];

        return $raw;
    }

    private function mergeExisting(array $raw, array $old): array
    {
        if (empty($raw['start_time'])) {
            $raw['start_time'] = $old['start_time'] ?? null;
        }

        if (empty($raw['end_time'])) {
            $raw['end_time'] = $old['end_time'] ?? null;
        }

        $raw['info'] = $old['info'] ?? [
            'detail' => [],
            'in' => false,
            'out' => false
        ];

        return $raw;
    }
}
