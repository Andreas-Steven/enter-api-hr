<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebtInstallment\Entity;

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

final class EmployeeDebtInstallment
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'EmployeeDebtInstallment';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private int $employeeDebtId,
        private ?int $payrollDeductionId,
        private int $sequence,
        private string $dueDate,
        private float $amount,
        private int $paymentStatus,
        private ?string $paidAt,
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
        int $employeeDebtId,
        ?int $payrollDeductionId,
        int $sequence,
        string $dueDate,
        float $amount,
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
            employeeDebtId: $employeeDebtId, 
            payrollDeductionId: $payrollDeductionId, 
            sequence: $sequence, 
            dueDate: $dueDate, 
            amount: $amount, 
            paymentStatus: 0,
            paidAt: null,
            status: $status, 
            detailInfo: $detailInfo, 
            syncMdb: $syncMdb, 
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        string $name,
        int $employeeDebtId,
        ?int $payrollDeductionId,
        int $sequence,
        string $dueDate,
        float $amount,
        int $paymentStatus,
        ?string $paidAt,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {
        return new self(
            id: $id, 
            name: $name, 
            employeeDebtId: $employeeDebtId, 
            payrollDeductionId: $payrollDeductionId, 
            sequence: $sequence, 
            dueDate: $dueDate, 
            amount: $amount, 
            paymentStatus: $paymentStatus,
            paidAt: $paidAt,
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
            'employee_debt_id' => $this->employeeDebtId,
            'payroll_deduction_id' => $this->payrollDeductionId,
            'sequence' => $this->sequence,
            'due_date' => $this->dueDate,
            'amount' => $this->amount,
            'payment_status' => $this->paymentStatus,
            'paid_at' => $this->paidAt,
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

    public function getEmployeeDebtId(): int
    {
        return $this->employeeDebtId;
    }

    public function getPayrollDeductionId(): ?int
    {
        return $this->payrollDeductionId;
    }

    public function linkPayrollDeduction(?int $payrollDeductionId): void
    {
        $this->payrollDeductionId = $payrollDeductionId;
    }

    public function getSequence(): int
    {
        return $this->sequence;
    }

    public function getDueDate(): string
    {
        return $this->dueDate;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getPaymentStatus(): int
    {
        return $this->paymentStatus;
    }

    public function isPaid(): bool
    {
        return $this->paymentStatus === 1;
    }

    public function markAsPaid(?int $payrollDeductionId = null): void
    {
        $this->paymentStatus = 1;
        $this->paidAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
        if ($payrollDeductionId !== null) {
            $this->payrollDeductionId = $payrollDeductionId;
        }
    }

    public function markAsWaived(): void
    {
        $this->paymentStatus = 2;
        $this->paidAt = null;
    }

    public function getPaidAt(): ?string
    {
        return $this->paidAt;
    }

    public function setEmployeeDebtId(int $employeeDebtId): void
    {
        $this->employeeDebtId = $employeeDebtId;
    }

    public function setSequence(int $sequence): void
    {
        $this->sequence = $sequence;
    }

    public function setDueDate(string $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }
}
