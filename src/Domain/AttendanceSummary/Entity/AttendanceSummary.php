<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\Entity;

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

final class AttendanceSummary
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'AttendanceSummary';
    public const TYPE_MONTHLY = 'MONTHLY';
    public const TYPE_PERIOD = 'PERIOD';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private int $employeeId,
        private int $branchId,
        private int $divisionId,
        private int $positionId,
        private int $employeeTypeId,
        private ?int $workPatternId,
        private ?int $period,
        private array $attendanceDetail,
        private array $periodSummary,
        private array $timeSummary,
        private ?string $notes,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
        private array $permissionDetail = [],
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
        int $employeeId,
        int $branchId,
        int $divisionId,
        int $positionId,
        int $employeeTypeId,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?array $permissionDetail = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null,
            name: $name,
            employeeId: $employeeId,
            branchId: $branchId,
            divisionId: $divisionId,
            positionId: $positionId,
            employeeTypeId: $employeeTypeId,
            workPatternId: null,
            period: null,
            attendanceDetail: [],
            periodSummary: [],
            timeSummary: [],
            notes: null,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create(),
            permissionDetail: $permissionDetail ?? [],
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        int $employeeId,
        int $branchId,
        int $divisionId,
        int $positionId,
        int $employeeTypeId,
        ?int $workPatternId,
        ?int $period,
        array $attendanceDetail,
        array $periodSummary,
        array $timeSummary,
        ?string $notes,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
        ?array $permissionDetail = null,
    ): self {

        return new self(
            id: $id,
            name: $name,
            employeeId: $employeeId,
            branchId: $branchId,
            divisionId: $divisionId,
            positionId: $positionId,
            employeeTypeId: $employeeTypeId,
            workPatternId: $workPatternId,
            period: $period,
            attendanceDetail: $attendanceDetail,
            periodSummary: $periodSummary,
            timeSummary: $timeSummary,
            notes: $notes,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: $lockVersion ?? LockVersion::create(),
            permissionDetail: $permissionDetail ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'employee_id' => $this->employeeId,
            'branch_id' => $this->branchId,
            'division_id' => $this->divisionId,
            'position_id' => $this->positionId,
            'employee_type_id' => $this->employeeTypeId,
            'work_pattern_id' => $this->workPatternId,
            'period' => $this->period,
            'attendance_detail' => $this->attendanceDetail,
            'permission_detail' => $this->permissionDetail,
            'period_summary' => $this->periodSummary,
            'time_summary' => $this->timeSummary,
            'notes' => $this->notes,
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

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function setEmployeeId(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function getBranchId(): int
    {
        return $this->branchId;
    }

    public function setBranchId(int $branchId): void
    {
        $this->branchId = $branchId;
    }

    public function getDivisionId(): int
    {
        return $this->divisionId;
    }

    public function setDivisionId(int $divisionId): void
    {
        $this->divisionId = $divisionId;
    }

    public function getPositionId(): int
    {
        return $this->positionId;
    }

    public function setPositionId(int $positionId): void
    {
        $this->positionId = $positionId;
    }

    public function getEmployeeTypeId(): int
    {
        return $this->employeeTypeId;
    }

    public function setEmployeeTypeId(int $employeeTypeId): void
    {
        $this->employeeTypeId = $employeeTypeId;
    }

    public function getWorkPatternId(): ?int
    {
        return $this->workPatternId;
    }

    public function setWorkPatternId(?int $workPatternId): void
    {
        $this->workPatternId = $workPatternId;
    }

    public function getPeriod(): ?int
    {
        return $this->period;
    }

    public function setPeriod(?int $period): void
    {
        $this->period = $period;
    }

    public function getAttendanceDetail(): array
    {
        return $this->attendanceDetail;
    }

    public function setAttendanceDetail(array $data): void
    {
        $this->attendanceDetail = $data;
    }

    public function getPermissionDetail(): array
    {
        return $this->permissionDetail;
    }

    public function setPermissionDetail(array $data): void
    {
        $this->permissionDetail = $data;
    }

    public function getPeriodSummary(): array
    {
        return $this->periodSummary;
    }

    public function setPeriodSummary(array $data): void
    {
        $this->periodSummary = $data;
    }

    public function getTimeSummary(): array
    {
        return $this->timeSummary;
    }

    public function setTimeSummary(array $data): void
    {
        $this->timeSummary = $data;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    public function isMonthly(): bool
    {
        return $this->getSummaryType() === self::TYPE_MONTHLY;
    }

    public function isPeriod(): bool
    {
        return $this->getSummaryType() === self::TYPE_PERIOD;
    }
}