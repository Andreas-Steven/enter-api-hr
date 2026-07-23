<?php

declare(strict_types=1);

namespace App\Domain\AttendanceCalendar\Entity;

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

use App\Domain\AttendanceCalendar\ValueObject\Scope;
use App\Domain\AttendanceCalendar\ValueObject\AttendancePeriod;

final class AttendanceCalendar
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'AttendanceCalendar';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private string $date,
        private AttendancePeriod $period,
        private string $holidayType,
        private Scope $scope,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
        private ?string $note = null,
        private int $dayOff = 0,
    ) {
        $this->resource = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public static function getResource(): string
    {
        return self::RESOURCE;
    }

    public static function create(
        string $name,
        string $date,
        string $holidayType,
        Scope $scope,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?string $note = null,
        int $dayOff = 0,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        $period = AttendancePeriod::fromDateString($date);

        return new self(
            id: null,
            name: $name,
            date: $date,
            period: $period,
            holidayType: $holidayType,
            scope: $scope,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create(),
            note: $note,
            dayOff: $dayOff
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        string $date,
        int $period,
        string $holidayType,
        Scope $scope,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
        ?string $note = null,
        int $dayOff = 0,
    ): self {

        return new self(
            id: $id,
            name: $name,
            date: $date,
            period: AttendancePeriod::fromInt($period),
            holidayType: $holidayType,
            scope: $scope,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: $lockVersion ?? LockVersion::create(),
            note: $note,
            dayOff: $dayOff
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'date' => $this->date,
            'period' => $this->period->toInt(),
            'holiday_type' => $this->holidayType(),
            'scope' => $this->scope->toArray(),
            'note' => $this->note,
            'day_off' => $this->dayOff,
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
     * Place AttendanceCalendar-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */ 
    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }
    
    public function getPeriod(): AttendancePeriod
    {
        return $this->period;
    }
    
    public function setPeriod(AttendancePeriod $period): void
    {
        $this->period = $period;
    }

    public function getHolidayType(): string
    {
        return $this->holidayType;
    }

    public function setHolidayType(string $holidayType): void
    {
        $this->holidayType = $holidayType;
    }

    public function getScope(): Scope
    {
        return $this->scope;
    }

    public function setScope(Scope $scope): void
    {
        $this->scope = $scope;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getDayOff(): int
    {
        return $this->dayOff;
    }

    public function setDayOff(int $dayOff): void
    {
        $this->dayOff = $dayOff;
    }
}
