<?php

declare(strict_types=1);

namespace App\Domain\AttendancePermission\Entity;

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
use App\Domain\Shared\Enums\PermissionType;
use App\Domain\Shared\Enums\ApprovalStatus;
use App\Domain\AttendancePermission\ValueObject\Revision;
use App\Domain\Attendance\ValueObject\AttendancePeriod;

final class AttendancePermission
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'AttendancePermission';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private int $employeeId,
        private int $permissionType,
        private string $startDate,
        private ?string $endDate,
        private int $period,
        private string $note,
        private ?int $approved,
        private ?string $approvedNote,
        private ?Revision $revision,
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
        int $employeeId,
        int $permissionType,
        string $startDate,
        ?string $endDate,
        int $period,
        string $note,
        ?int $approved,
        ?string $approvedNote,
        ?Revision $revision,
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
            employeeId: $employeeId,
            permissionType: $permissionType,
            startDate: $startDate,
            endDate: $endDate,
            period: $period,
            note: $note,
            approved: $approved,
            approvedNote: $approvedNote,
            revision: $revision,
            status: $status, 
            detailInfo: $detailInfo, 
            syncMdb: $syncMdb, 
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        int $employeeId,
        int $permissionType,
        string $startDate,
        ?string $endDate,
        int $period,
        string $note,
        ?int $approved,
        ?string $approvedNote,
        ?Revision $revision,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {

        return new self(
            id: $id, 
            employeeId: $employeeId,
            permissionType: $permissionType,
            startDate: $startDate,
            endDate: $endDate,
            period: $period,
            note: $note,
            approved: $approved,
            approvedNote: $approvedNote,
            revision: $revision,
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
            'employee_id' => $this->employeeId,
            'permission_type' => $this->permissionType,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'period' => $this->period,
            'note' => $this->note,
            'revision' => $this->revision?->toArray(),
            'approved' => $this->approved,
            'approved_note' => $this->approvedNote,
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
     * Place AttendancePermission-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc).
     */ 

    // Override Identifiable trait methods since we don't use name
    public function getName(): string
    {
        return ''; // Return empty string since name is not used
    }

    public function updateName(?string $newName): void
    {
        // Do nothing since name is not used
    } 

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function setEmployeeId(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function updateEmployeeId(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function getPermissionType(): int
    {
        return $this->permissionType;
    }

    public function setPermissionType(int $permissionType): void
    {
        $this->permissionType = $permissionType;
    }

    public function updatePermissionType(int $permissionType): void
    {
        $this->permissionType = $permissionType;
    }

        public function getPermission(): PermissionType
    {
        if (is_int($this->permissionType)) {
            return PermissionType::from($this->permissionType);
        }
        
        return $this->permissionType;
    }

    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    public function setStartDate(string $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function updateStartDate(string $startDate): void
    {
        $this->startDate = $startDate;
        $this->period = AttendancePeriod::fromDateString($startDate)->toInt();
    }

    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    public function setEndDate(?string $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function updateEndDate(?string $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(string $note): void
    {
        $this->note = $note;
    }

    public function updateNote(string $note): void
    {
        $this->note = $note;
    }

    public function getRevision(): ?Revision
    {
        return $this->revision;
    }

    public function clearRevision(): void
    {
        $this->revision = null;
    }

    public function getApproved(): ?int
    {
        return $this->approved;
    }

    public function getApprovedNote(): ?string
    {
        return $this->approvedNote;
    }

    public function updateRevision(Revision $newRevision): void
    {
        $this->revision = $newRevision;
    }

    /**
     * Set approval status
     */
    public function applyApproval(ApprovalStatus $newStatus, ?string $note): void
    {
        // 1. Ambil status saat ini dari property int (default ke PENDING jika null)
        $currentStatus = ApprovalStatus::tryFrom($this->approved ?? ApprovalStatus::PENDING->value);

        // 2. Gunakan guard logic yang sudah dipindah ke Enum
        if (!$newStatus->canTransitionFrom($currentStatus)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_permission.invalid_approval_transition',
                    params: [
                        'from' => $currentStatus->getLabel(),
                        'to' => $newStatus->getLabel(),
                    ],
                    domain: 'app',
                )
            );
        }

        // 3. Jika valid, update state
        $this->approved = $newStatus->value;
        $this->approvedNote = $note;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function setPeriod(int $period): void
    {
        $this->period = $period;
    }
}