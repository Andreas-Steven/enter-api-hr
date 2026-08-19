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
use App\Domain\AttendanceSummary\ValueObject\Name;
use App\Domain\AttendanceSummary\ValueObject\Period;
use App\Domain\AttendanceSummary\ValueObject\Notes;
use App\Domain\AttendanceSummary\ValueObject\EmployeeSnapshot;
use App\Domain\AttendanceSummary\ValueObject\WorkSchedule;
use App\Domain\AttendanceSummary\ValueObject\AttendanceData;
use App\Domain\AttendanceSummary\ValueObject\PermissionCollection;
use App\Domain\AttendanceSummary\ValueObject\Summary;
use App\Domain\AttendanceSummary\ValueObject\Policy;

final class AttendanceSummary
{
    use Identifiable;
    use Stateful;
    use Descriptive;

    public const RESOURCE = 'AttendanceSummary';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private ?int $period,
        private readonly EmployeeSnapshot $employee,
        private readonly WorkSchedule $workSchedule,
        private readonly AttendanceData $attendance,
        private readonly PermissionCollection $permissions,
        private readonly Summary $summary,
        private readonly Policy $policy,
        private ?string $notes,
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

    /**
     * Creates a new auto-generated AttendanceSummary.
     *
     * The name, employee snapshot, work schedule, attendance data, permissions,
     * summary (calculated) and policy are produced by the generation flow from
     * the Attendance and Employee aggregates.
     */
    public static function create(
        Name $name,
        EmployeeSnapshot $employee,
        WorkSchedule $workSchedule,
        AttendanceData $attendance,
        PermissionCollection $permissions,
        Summary $summary,
        Policy $policy,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?Period $period = null,
        ?Notes $notes = null,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE,
        );

        return new self(
            id: null,
            name: $name->toString(),
            period: $period?->toInt(),
            employee: $employee,
            workSchedule: $workSchedule,
            attendance: $attendance,
            permissions: $permissions,
            summary: $summary,
            policy: $policy,
            notes: $notes?->toString(),
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create(),
        );
    }

    /**
     * Reconstitutes an AttendanceSummary from persisted (raw) data.
     *
     * Composite columns are passed as arrays and wrapped in their Value Objects.
     */
    public static function reconstitute(
        int $id,
        string $name,
        ?int $period,
        array $employee,
        array $workSchedule,
        array $attendance,
        array $permissions,
        array $summary,
        array $policy,
        ?string $notes,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            period: $period,
            employee: EmployeeSnapshot::fromArray($employee),
            workSchedule: WorkSchedule::fromArray($workSchedule),
            attendance: AttendanceData::fromArray($attendance),
            permissions: PermissionCollection::fromArray($permissions),
            summary: Summary::fromArray($summary),
            policy: Policy::fromArray($policy),
            notes: $notes,
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
            'employee' => $this->employee->toArray(),
            'work_schedule' => $this->workSchedule->toArray(),
            'attendance' => $this->attendance->toArray(),
            'permissions' => $this->permissions->toArray(),
            'summary' => $this->summary->toArray(),
            'policy' => $this->policy->toArray(),
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
                    params: ['id' => $this->id],
                ),
            );
        }

        $this->status = ResourceStatus::restored();
    }

    /**
     * Auto-generated summary fields.
     */
    public function getPeriod(): ?int
    {
        return $this->period;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function getEmployee(): EmployeeSnapshot
    {
        return $this->employee;
    }

    public function getWorkSchedule(): WorkSchedule
    {
        return $this->workSchedule;
    }

    public function getAttendance(): AttendanceData
    {
        return $this->attendance;
    }

    public function getPermissions(): PermissionCollection
    {
        return $this->permissions;
    }

    public function getSummary(): Summary
    {
        return $this->summary;
    }

    public function getPolicy(): Policy
    {
        return $this->policy;
    }

    /**
     * Convenience accessors consumed by downstream modules (e.g. PayrollReport
     * generation). These flatten the Value-Object graph into the legacy array
     * shapes expected by those consumers.
     */
    public function getEmployeeId(): ?int
    {
        return $this->employee->getEmployeeId();
    }

    /** @return list<array<string, mixed>> */
    public function getAttendanceDetail(): array
    {
        return $this->attendance->toArray();
    }

    /** @return array<int, mixed> */
    public function getPermissionDetail(): array
    {
        return $this->permissions->toArray();
    }

    /** @return array<string, int|array> */
    public function getPeriodSummary(): array
    {
        $schedule = $this->summary->getSchedule();
        $attendance = $this->summary->getAttendance();
        $compliance = $this->summary->getCompliance();
        $overtime = $this->summary->getOvertime();
        $deduction = $this->summary->getDeduction();
        $leave = $this->summary->getLeave();

        return [
            'hek_count' => $schedule->getHekDays(),
            'obligation_count' => $schedule->getObligationDays(),
            'total_working_days_count' => $schedule->getTotalWorkingDays(),
            'obligation_unfulfilled_count' => $compliance->getUnfulfilled(),
            'present_count' => $attendance->getPresentDays(),
            'absent_count' => $attendance->getAbsentDays(),
            'sick_count' => $attendance->getSickDays(),
            'leave_count' => $attendance->getLeaveDays(),
            'excess_count' => $compliance->getExcess(),
            'overtime_day_count' => $overtime->getDays(),
            'overtime_minute_count' => $overtime->getMinutes(),
            'leave_quota_used_count' => $leave->getQuotaUsed(),
            'deduction_day_count' => $deduction->getDays(),
            'pending_review_count' => $compliance->getPendingReview(),
            'rule_breakdown' => $this->summary->getRules(),
        ];
    }

    /** @return array<string, int> */
    public function getTimeSummary(): array
    {
        $time = $this->summary->getTime();

        return [
            'late_count' => $time->getLateCount(),
            'late_minutes' => $time->getLateMinutes(),
            'early_out_count' => $time->getEarlyOutCount(),
            'early_out_minutes' => $time->getEarlyOutMinutes(),
            'incomplete_count' => $time->getIncomplete(),
        ];
    }

    /**
     * Manual adjustments on a generated summary.
     */
    public function updateNotes(?Notes $notes): void
    {
        if ($notes === null) {
            return;
        }

        $this->notes = $notes->toString();
    }
}
