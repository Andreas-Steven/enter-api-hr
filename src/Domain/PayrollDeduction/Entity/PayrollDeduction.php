<?php

declare(strict_types=1);

namespace App\Domain\PayrollDeduction\Entity;

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

final class PayrollDeduction
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'PayrollDeduction';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private int $employeeId,
        private int $deductionTypeId,
        private ?int $attendanceReportId,
        private ?int $employeeDebtId,
        private ?int $employeeDebtInstallmentId,
        private int $period,
        private float $amount,
        private ?string $description,
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
        int $employeeId,
        int $deductionTypeId,
        ?int $attendanceReportId,
        ?int $employeeDebtId,
        ?int $employeeDebtInstallmentId,
        int $period,
        float $amount,
        ?string $description,
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
            name: $name, 
            employeeId: $employeeId, 
            deductionTypeId: $deductionTypeId, 
            attendanceReportId: $attendanceReportId, 
            employeeDebtId: $employeeDebtId, 
            employeeDebtInstallmentId: $employeeDebtInstallmentId, 
            period: $period, 
            amount: $amount, 
            description: $description, 
            status: $status, 
            detailInfo: $detailInfo, 
            syncMdb: $syncMdb, 
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        int $employeeId,
        int $deductionTypeId,
        ?int $attendanceReportId,
        ?int $employeeDebtId,
        ?int $employeeDebtInstallmentId,
        int $period,
        float $amount,
        ?string $description,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {
        return new self(
            id: $id, 
            name: $name, 
            employeeId: $employeeId, 
            deductionTypeId: $deductionTypeId, 
            attendanceReportId: $attendanceReportId, 
            employeeDebtId: $employeeDebtId, 
            employeeDebtInstallmentId: $employeeDebtInstallmentId, 
            period: $period, 
            amount: $amount, 
            description: $description, 
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
            'employee_id' => $this->employeeId,
            'deduction_type_id' => $this->deductionTypeId,
            'attendance_report_id' => $this->attendanceReportId,
            'employee_debt_id' => $this->employeeDebtId,
            'employee_debt_installment_id' => $this->employeeDebtInstallmentId,
            'period' => $this->period,
            'amount' => $this->amount,
            'description' => $this->description,
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

    public function getDeductionTypeId(): int
    {
        return $this->deductionTypeId;
    }

    public function getAttendanceReportId(): ?int
    {
        return $this->attendanceReportId;
    }

    public function getEmployeeDebtId(): ?int
    {
        return $this->employeeDebtId;
    }

    public function getEmployeeDebtInstallmentId(): ?int
    {
        return $this->employeeDebtInstallmentId;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setEmployeeId(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function setDeductionTypeId(int $deductionTypeId): void
    {
        $this->deductionTypeId = $deductionTypeId;
    }

    public function setAttendanceReportId(?int $attendanceReportId): void
    {
        $this->attendanceReportId = $attendanceReportId;
    }

    public function setEmployeeDebtId(?int $employeeDebtId): void
    {
        $this->employeeDebtId = $employeeDebtId;
    }

    public function setEmployeeDebtInstallmentId(?int $employeeDebtInstallmentId): void
    {
        $this->employeeDebtInstallmentId = $employeeDebtInstallmentId;
    }

    public function setPeriod(int $period): void
    {
        $this->period = $period;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
