<?php

declare(strict_types=1);

namespace App\Domain\AttendancePeriod\Entity;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

use DateTimeImmutable;

// Domain Layer
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;
use App\Domain\AttendancePeriod\ValueObject\StartDate;
use App\Domain\AttendancePeriod\ValueObject\EndDate;

use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;

final class AttendancePeriod
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'AttendancePeriod';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private int $period,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?StartDate $startDate = null,
        private ?EndDate $endDate = null,
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
        int $period,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?StartDate $startDate = null,
        ?EndDate $endDate = null,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null, 
            name: $name, 
            period: $period,
            startDate: $startDate,
            endDate: $endDate,
            status: $status, 
            detailInfo: $detailInfo, 
            syncMdb: $syncMdb, 
            lockVersion: LockVersion::create(),
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        int $period,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?StartDate $startDate = null,
        ?EndDate $endDate = null,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self(
            id: $id, 
            name: $name, 
            period: $period,
            startDate: $startDate,
            endDate: $endDate,
            status: $status, 
            detailInfo: $detailInfo, 
            syncMdb: $syncMdb, 
            lockVersion: $lockVersion ?? LockVersion::create(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'period' => $this->period,
            'start_date' => $this->getStartDate()?->toString(),
            'end_date' => $this->getEndDate()?->toString(),
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
     * Place AttendancePeriod-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */ 
    public function getPeriod(): int
    {
        return $this->period;
    }   

    public function getStartDate(): ?StartDate
    {
        return $this->startDate;
    }

    public function getEndDate(): ?EndDate
    {
        return $this->endDate;
    }

    public function updateStartDate(?DateTimeImmutable $newStartDate): void
    {
        $this->startDate = StartDate::fromDateTimeImmutable($newStartDate);
    }

    public function updateEndDate(?DateTimeImmutable $newEndDate): void
    {
        $this->endDate = EndDate::fromDateTimeImmutable($newEndDate);
    }

}