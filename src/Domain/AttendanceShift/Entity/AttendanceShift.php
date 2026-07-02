<?php

declare(strict_types=1);

namespace App\Domain\AttendanceShift\Entity;

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
use App\Domain\AttendanceShift\ValueObject\DefaultWorkTime;

final class AttendanceShift
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'AttendanceShift';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private string $startTime,
        private string $endTime,
        private int $breakMinutes,
        private int $crossDay,
        private DefaultWorkTime $defaultWorkTime,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?string $note = null,
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
        string $name,
        string $startTime,
        string $endTime,
        int $breakMinutes,
        int $crossDay,
        DefaultWorkTime $defaultWorkTime,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?string $note = null,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null, 
            name: $name, 
            startTime: $startTime,
            endTime: $endTime,
            breakMinutes: $breakMinutes,
            crossDay: $crossDay,
            defaultWorkTime: $defaultWorkTime,
            note: $note,
            status: $status, 
            detailInfo: $detailInfo, 
            syncMdb: $syncMdb, 
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        string $startTime,
        string $endTime,
        int $breakMinutes,
        int $crossDay,
        int $defaultWorkTime,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?string $note = null,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self(
            id: $id, 
            name: $name, 
            startTime: $startTime,
            endTime: $endTime,
            breakMinutes: $breakMinutes,
            crossDay: $crossDay,
            defaultWorkTime: DefaultWorkTime::fromInt($defaultWorkTime),
            note: $note,
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
            'name' => $this->name,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'break_minutes' => $this->breakMinutes,
            'cross_day' => $this->crossDay,
            'default_work_time' => $this->defaultWorkTime->value(),
            'note' => $this->note,
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
     * Place AttendanceShift-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */ 
    public function getStartTime(): string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): void
    {
        $this->startTime = $startTime;
    }

    public function getEndTime(): string
    {
        return $this->endTime;
    }

    public function setEndTime(string $endTime): void
    {
        $this->endTime = $endTime;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getBreakMinutes(): int
    {
        return $this->breakMinutes;
    }

    public function setBreakMinutes(int $breakMinutes): void
    {
        $this->breakMinutes = $breakMinutes;
        $this->recalculateDefaultWorkTime();
    }

    public function getCrossDay(): int
    {
        return $this->crossDay;
    }

    public function setCrossDay(int $crossDay): void
    {
        $this->crossDay = $crossDay;
        $this->recalculateDefaultWorkTime();
    }

    public function getDefaultWorkTime(): DefaultWorkTime
    {
        return $this->defaultWorkTime;
    }

    public function updateTimes(
        string $startTime,
        string $endTime,
        int $breakMinutes,
        int $crossDay
    ): void {
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->breakMinutes = $breakMinutes;
        $this->crossDay = $crossDay;
        $this->recalculateDefaultWorkTime();
    }

    private function recalculateDefaultWorkTime(): void
    {
        $this->defaultWorkTime = DefaultWorkTime::fromTimeRange(
            startTime: $this->startTime,
            endTime: $this->endTime,
            breakMinutes: $this->breakMinutes,
            crossDay: $this->crossDay
        );
    }
}