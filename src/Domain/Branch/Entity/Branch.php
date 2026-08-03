<?php

declare(strict_types=1);

namespace App\Domain\Branch\Entity;

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

final class Branch
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'Branch';

    /**
     * Code that identifies the placeholder branch used when an employee
     * has not been assigned to a real branch yet. This branch is allowed
     * to be activated without the usual required fields.
     */
    public const UNASSIGN_CODE = 'UNASSIGN';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private string $code,
        private string $address,
        private ?int $attendancePoliciesId,
        private ?int $workPatternId,
        private ResourceStatus $status,
        private ?int $branchType,
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
        string $code,
        string $address,
        ?int $attendancePoliciesId,
        ?int $workPatternId,
        ResourceStatus $status,
        ?int $branchType,
        DetailInfo $detailInfo,
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
            address: $address,
            attendancePoliciesId: $attendancePoliciesId,
            workPatternId: $workPatternId,
            status: $status,
            branchType: $branchType,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        string $code,
        string $address,
        ?int $attendancePoliciesId,
        ?int $workPatternId,
        ResourceStatus $status,
        ?int $branchType,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self(
            id: $id,
            name: $name,
            code: $code,
            address: $address,
            attendancePoliciesId: $attendancePoliciesId,
            workPatternId: $workPatternId,
            status: $status,
            branchType: $branchType,
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
            'code' => $this->code,
            'address' => $this->address,
            'attendance_policies_id' => $this->attendancePoliciesId,
            'work_pattern_id' => $this->workPatternId,
            'status' => $this->status->value(),
            'branch_type' => $this->branchType,
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
     * Place Branch-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */ 
    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function updateCode(?string $newCode): void
    {
        if ($newCode === null) {
            return;
        }

        $this->code = $newCode;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function updateAddress(?string $newAddress): void
    {
        if ($newAddress === null) {
            return;
        }

        $this->address = $newAddress;
    }

    public function getType(): ?int
    {
        return $this->branchType;
    }

    public function getAttendancePoliciesId(): ?int
    {
        return $this->attendancePoliciesId;
    }

    public function updateAttendancePoliciesId(?int $attendancePoliciesId): void
    {
        $this->attendancePoliciesId = $attendancePoliciesId;
    }

    public function getWorkPatternId(): ?int
    {
        return $this->workPatternId;
    }

    public function updateWorkPatternId(?int $workPatternId): void
    {
        $this->workPatternId = $workPatternId;
    }

    public function updateType(?int $newType): void
    {
        $this->branchType = $newType;
    }
}
