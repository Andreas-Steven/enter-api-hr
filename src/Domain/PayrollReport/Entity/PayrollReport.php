<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\Entity;

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

use App\Domain\PayrollReport\ValueObject\Name;
use App\Domain\PayrollReport\ValueObject\Period;
use App\Domain\PayrollReport\ValueObject\Notes;
use App\Domain\PayrollReport\ValueObject\EmployeeSnapshot;
use App\Domain\PayrollReport\ValueObject\AttendanceSummaryRef;
use App\Domain\PayrollReport\ValueObject\PayrollSummary;

final class PayrollReport
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'PayrollReport';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private ?int $period,
        private EmployeeSnapshot $employee,
        private AttendanceSummaryRef $attendanceSummary,
        private PayrollSummary $payrollSummary,
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

    public static function create(
        string $name,
        ?int $period,
        EmployeeSnapshot $employee,
        AttendanceSummaryRef $attendanceSummary,
        PayrollSummary $payrollSummary,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?Notes $notes = null,
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
            employee: $employee,
            attendanceSummary: $attendanceSummary,
            payrollSummary: $payrollSummary,
            notes: $notes?->toString(),
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        ?int $period,
        array $employee,
        array $attendanceSummary,
        array $payrollSummary,
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
            attendanceSummary: AttendanceSummaryRef::fromArray($attendanceSummary),
            payrollSummary: PayrollSummary::fromArray($payrollSummary),
            notes: $notes,
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
            'period' => $this->period,
            'employee' => $this->employee->toArray(),
            'attendance_summary' => $this->attendanceSummary->toArray(),
            'payroll_summary' => $this->payrollSummary->toArray(),
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

    /**
     * Auto-generated payroll report fields.
     */
    public function getPeriod(): ?int
    {
        return $this->period;
    }

    public function getEmployee(): EmployeeSnapshot
    {
        return $this->employee;
    }

    public function getAttendanceSummary(): AttendanceSummaryRef
    {
        return $this->attendanceSummary;
    }

    public function getPayrollSummary(): PayrollSummary
    {
        return $this->payrollSummary;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function updateNotes(?Notes $notes): void
    {
        $this->notes = $notes?->toString();
    }

    public function updatePayrollSummary(PayrollSummary $payrollSummary): void
    {
        $this->payrollSummary = $payrollSummary;
    }

    public function updateEmployee(EmployeeSnapshot $employee): void
    {
        $this->employee = $employee;
    }

    public function updateAttendanceSummary(AttendanceSummaryRef $attendanceSummary): void
    {
        $this->attendanceSummary = $attendanceSummary;
    }
}
