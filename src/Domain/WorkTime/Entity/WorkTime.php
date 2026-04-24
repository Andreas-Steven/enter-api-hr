<?php

declare(strict_types=1);

namespace App\Domain\WorkTime\Entity;

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
use App\Domain\WorkTime\ValueObject\WorkingDays;

final class WorkTime
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'Work Time';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private WorkingDays $workingDays,
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
        string $name,
        WorkingDays $workingDays,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(null, $name, $workingDays, $status, $detailInfo, $syncMdb, LockVersion::create());
    }

    public static function reconstitute(
        int $id,
        string $name,
        WorkingDays $workingDays,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self($id, $name, $workingDays, $status, $detailInfo, $syncMdb, $lockVersion ?? LockVersion::create());
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'working_days' => $this->getWorkingDays()->toArray(),
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            SyncMdb::field() => $this->syncMdb?->value(),
            LockVersion::field() => $this->lockVersion->value(),
        ];
    }

    public function toPersistence(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'working_days' => $this->getWorkingDays()->toArray(),
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
     * Place WorkTime-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */ 

    public function getWorkingDays(): WorkingDays 
    {
        return $this->workingDays;
    }
    
    public function updateWorkingDays(WorkingDays $workingDays): void
    {
        $this->workingDays = $workingDays;
    }
}
