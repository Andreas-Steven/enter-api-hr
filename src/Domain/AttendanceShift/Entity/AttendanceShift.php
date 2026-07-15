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
use App\Domain\AttendanceShift\ValueObject\StartTime;
use App\Domain\AttendanceShift\ValueObject\EndTime;

final class AttendanceShift
{
    use Identifiable;
    use Stateful;
    use Descriptive;

    public const RESOURCE = 'AttendanceShift';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private StartTime $startTime,
        private EndTime $endTime,
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
        StartTime $startTime,
        EndTime $endTime,
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
            resource: self::RESOURCE,
        );

        return new self(
            id: null,
            name: $name,
            startTime: $startTime,
            endTime: $endTime,
            breakMinutes: $breakMinutes,
            crossDay: $crossDay,
            defaultWorkTime: $defaultWorkTime,
            status: $status,
            detailInfo: $detailInfo,
            note: $note,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create(),
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
            startTime: StartTime::fromString($startTime),
            endTime: EndTime::fromString($endTime),
            breakMinutes: $breakMinutes,
            crossDay: $crossDay,
            defaultWorkTime: DefaultWorkTime::fromInt($defaultWorkTime),
            status: $status,
            detailInfo: $detailInfo,
            note: $note,
            syncMdb: $syncMdb,
            lockVersion: $lockVersion ?? LockVersion::create(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_time' => $this->startTime->toString(),
            'end_time' => $this->endTime->toString(),
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
                    params: ['id' => $this->id],
                ),
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
        return $this->startTime->toString();
    }

    public function setStartTime(string $startTime): void
    {
        $this->startTime = StartTime::fromString($startTime);
    }

    public function getEndTime(): string
    {
        return $this->endTime->toString();
    }

    public function setEndTime(string $endTime): void
    {
        $this->endTime = EndTime::fromString($endTime);
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
        int $crossDay,
    ): void {
        $this->startTime = StartTime::fromString($startTime);
        $this->endTime = EndTime::fromString($endTime);
        $this->breakMinutes = $breakMinutes;
        $this->crossDay = $crossDay;
        $this->recalculateDefaultWorkTime();
    }

    private function recalculateDefaultWorkTime(): void
    {
        $this->defaultWorkTime = DefaultWorkTime::fromTimeRange(
            startTime: $this->startTime->toString(),
            endTime: $this->endTime->toString(),
            breakMinutes: $this->breakMinutes,
            crossDay: $this->crossDay,
        );
    }
}
