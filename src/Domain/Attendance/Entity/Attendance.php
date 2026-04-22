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
use App\Domain\Attendance\ValueObject\AttendanceRecords;
use App\Domain\Attendance\ValueObject\AttendanceItem;


final class Attendance
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'Attendance';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $date,
        private AttendancePeriod $period,
        private AttendanceRecords $attendance,
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
        array $attendance,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        // 2. Otomatisasi pembuatan Period dari date
        $period = AttendancePeriod::fromDateString($date);

        return new self(
            id: null, 
            date: $date, 
            period: $period,
            attendance: AttendanceRecords::fromArray($attendance), 
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
            period: AttendancePeriod::fromInt($period),
            attendance: AttendanceRecords::fromArray($attendance), 
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
            'period' => AttendancePeriod::fromInt((int)$this->period),
            'attendance' => $this->attendance->getItems(),
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
        return $this->period->toInt();
    }

    public function getAttendance(): array
    {
        return $this->attendance->getItems();
    }

    public function updateEntries(array $entries): void
    {
        // 1. Pastikan $newItems adalah objek AttendanceItem
        $newItems = array_map(fn(array $entry) => AttendanceItem::fromArray($entry), $entries);

        // 2. Ambil item lama dan pastikan mereka juga menjadi objek
        $oldItems = $this->attendance->getItems();

        
        $itemMap = [];
        foreach ($oldItems as $item) {
            // PERBAIKAN: Jika item masih array, ubah jadi objek AttendanceItem
            if (is_array($item)) {
                $item = AttendanceItem::fromArray($item);
            }
            
            // Sekarang $item pasti objek, aman mengakses ->employeeId
            $itemMap[$item->employeeId] = $item;
        }

        foreach ($newItems as $newItem) {
            if (isset($itemMap[$newItem->employeeId])) {
                $oldItem = $itemMap[$newItem->employeeId];
                
                // A. Kunci startTime: Data lama (08:00) tetap menang, abaikan input baru
                if ($oldItem->startTime !== null) {
                    $newItem = $newItem->forceStartTime($oldItem->startTime);
                }

                #$newItem = $newItem->withLatestEndTime($newItem->endTime);
            }
            
            $itemMap[$newItem->employeeId] = $newItem;
        }

        // 4. Pastikan AttendanceRecords menerima array of Objects
        $this->attendance = AttendanceRecords::fromItems(array_values($itemMap));
    }
}