<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDivision\Entity;

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

final class EmployeeDivision
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'EmployeeDivision';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private string $code,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
        private ?int $workPatternId = null,
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
        string $code,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?int $workPatternId = null,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null,
            name: $name,
            code: $code,
            status: $status,
            detailInfo: $detailInfo,
            workPatternId: $workPatternId,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        string $code,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?int $workPatternId = null,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self(
            id: $id,
            name: $name,
            code: $code,
            status: $status,
            detailInfo: $detailInfo,
            workPatternId: $workPatternId,
            syncMdb: $syncMdb,
            lockVersion: $lockVersion ?? LockVersion::create()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'work_pattern_id' => $this->workPatternId,
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
            'code' => $this->code,
            'work_pattern_id' => $this->workPatternId,
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
     * Place EmployeeDivision-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */

    public function setCode(string $code): void
    {
        $this->code = strtoupper($code);
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getWorkPatternId(): ?int
    {
        return $this->workPatternId;
    }

    public function setWorkPatternId(?int $workPatternId): void
    {
        $this->workPatternId = $workPatternId;
    }

}
