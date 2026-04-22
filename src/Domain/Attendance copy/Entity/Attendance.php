<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Entity;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

// Domain Layer
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;

use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;
use App\Domain\Shared\Enums\AttendanceStatus;
use App\Domain\Attendance\ValueObject\AttendancePeriod;
use App\Domain\Attendance\ValueObject\AttendanceRecord;

final class Attendance
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'Attendance';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $date,
        private int $period,
        private array $attendance,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ) {
        $this->resource = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public static function getResource(): string
    {
        return self::RESOURCE;
    }

    public static function create(
        string $date,
        int $period,
        array $attendance,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null, 
            date: $date, 
            period: AttendancePeriod::fromDate($date)->value(),
            attendance: $attendance, 
            status: $status, 
            detailInfo: $detailInfo, 
            syncMdb: $syncMdb, 
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $date,
        int $period,
        array $attendance,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self(
            id: $id, 
            date: $date, 
            period: $period,
            attendance: $attendance, 
            status: $status, 
            detailInfo: $detailInfo, 
            syncMdb: $syncMdb, 
            lockVersion: $lockVersion ?? LockVersion::create()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'date' => $this->date,
            'period' => $this->period,
            'attendance' => $this->attendance,
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            SyncMdb::field() => $this->syncMdb?->value(),
            LockVersion::field() => $this->lockVersion->value(),
        ];
    }
    
    public function restore(): void
    {
        if (!$this->status->isDeleted()) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'resource.not_deleted',
                    params: ['id' => $this->id]
                )
            );
        }
        
        $this->status = ResourceStatus::restored();
    }

    /**
     * Place Attendance-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */ 
    public function getDate(): string
    {
        return $this->date;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getAttendance(): array
    {
        return $this->attendance;
    }

    /**
     * Synchronize Attendance records with business rules
     */
    private function getLastStartTime($attendance): ?string
    {
        if (empty($attendance)) {
            return null;
        }

        $startTimes = array_column($attendance, 'start_time');
        $startTimes = array_filter($startTimes);
        return !empty($startTimes) ? end($startTimes) : null;
    }

    private function findRecordIndexByEmployeeId(int $employeeId): ?int
    {
        foreach ($this->attendance as $index => $record) {
            if ($record['employee_id'] === $employeeId) {
                return $index;
            }
        }

        return null;
    }

    public function syncRecords(array $rawAttendanceData, bool $isDayOff = false, ?string $dateMismatch = null): void
    {
        $existingRecords = $this->attendance;

        foreach ($rawAttendanceData as $data) {
            $employeeId = (int) $data['employee_id'];
            $existingIndex = $this->findRecordIndexByEmployeeId($employeeId);

            if ($existingIndex !== null) {
                // UPDATE EXISTING RECORD
                $existingData = $existingRecords[$existingIndex];

                // merge start_time / end_time
                $mergedData = array_merge($existingData, $data);
                $record = AttendanceRecord::fromArray($mergedData);

            } else {
                // CREATE NEW RECORD
                $record = AttendanceRecord::fromArray($data);
            }

            $lastStartTime = $this->getLastStartTime($existingRecords);
            $record = $record->evaluateBusinessRules(
                date: $this->date,
                isDayOff: $isDayOff,
                lastStartTime: $lastStartTime,
                dateMismatch: $dateMismatch
            );

            if ($existingIndex !== null) {
                $existingRecords[$existingIndex] = $record->toArray();
            } else {
                $existingRecords[] = $record->toArray();
            }
        }

        $this->attendance = $existingRecords;
    }

    /**
     * Get blacklisted employees
     */
    // public function getBlacklistedEmployees(array $rawAttendanceData): array
    // {
    //     $list = [];
        
    //     // Buat map payload mentah agar pencarian cepat
    //     $rawPayloadMap = [];
    //     foreach ($rawAttendanceData as $raw) {
    //         $rawPayloadMap[(int)$raw['employee_id']] = $raw;
    //     }

    //     foreach ($this->attendance as $row) {
    //         $empId = (int) $row['employee_id'];
            
    //         // Cek apakah statusnya BLACKLIST
    //         if (in_array(\App\Domain\Shared\Enums\AttendanceStatus::BLACKLIST, $row['info']['detail'] ?? [], true)) {
                
    //             // AMBIL DATA ASLI DARI PAYLOAD, BUKAN DARI $row
    //             $payload = $rawPayloadMap[$empId] ?? null;
                
    //             // Jika payload tidak ada (misal update record lama), fallback ke data row
    //             $reportedStartTime = $payload ? ($payload['start_time'] ?? '') : ($row['start_time'] ?? '');

    //             $list[] = [
    //                 'employee_id'   => $empId,
    //                 'employee_name' => $row['employee_name'],
    //                 'date'          => $this->date,
    //                 'start_time'    => (string) $reportedStartTime, // Ini akan tetap "" atau null sesuai input user
    //                 'actual_time'   => date('H:i:s'),
    //                 'reason'        => 'Backdated/Mismatch'
    //             ];
    //         }
    //     }
        
    //     return $list;
    // }

    // public function getExistingBlacklists(): array
    // {
    //     $detail = $this->getDetailInfo()->toArray();
    //     $rawBlacklists = $detail['blacklists'] ?? [];
        
    //     return array_map(
    //         fn(array $item) => Blacklist::fromArray($item),
    //         $rawBlacklists
    //     );
    // }

    /**
     * Missed Check-out
     */
    public function markMissedCheckouts(): void
    {
        $updatedRecords = $this->attendance;
        $hasChange = false;

        foreach ($updatedRecords as $key => $record) {
            if (empty($record['end_time'])) {
                $details = $record['info']['detail'] ?? [];
                if (!in_array('missed_checkout', $details, true)) {
                    $details[] = 'missed_checkout';
                    $updatedRecords[$key]['info']['detail'] = $details;
                    $updatedRecords[$key]['info']['out'] = false;
                    $hasChange = true;
                }
            }
        }

        if ($hasChange) {
            $this->attendance = $updatedRecords;
        }
    }

    public function complete(): void
    {
        $this->status = ResourceStatus::completed(); 
    }
}