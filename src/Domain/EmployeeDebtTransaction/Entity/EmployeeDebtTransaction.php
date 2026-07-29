<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebtTransaction\Entity;

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

final class EmployeeDebtTransaction
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'EmployeeDebtTransaction';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private int $employeeDebtId,
        private ?int $employeeDebtInstallmentId,
        private ?int $payrollDeductionId,
        private string $type,
        private float $amount,
        private string $transactionDate,
        private ?string $note,
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
        ?int $employeeDebtInstallmentId,
        ?int $payrollDeductionId,
        string $type,
        float $amount,
        string $transactionDate,
        ?string $note,
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
            employeeDebtInstallmentId: $employeeDebtInstallmentId, 
            payrollDeductionId: $payrollDeductionId, 
            type: $type, 
            amount: $amount, 
            transactionDate: $transactionDate, 
            note: $note, 
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
        ?int $employeeDebtInstallmentId,
        ?int $payrollDeductionId,
        string $type,
        float $amount,
        string $transactionDate,
        ?string $note,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self {
        return new self(
            id: $id, 
            name: $name, 
            employeeDebtId: $employeeDebtId, 
            employeeDebtInstallmentId: $employeeDebtInstallmentId, 
            payrollDeductionId: $payrollDeductionId, 
            type: $type, 
            amount: $amount, 
            transactionDate: $transactionDate, 
            note: $note, 
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
            'employee_debt_installment_id' => $this->employeeDebtInstallmentId,
            'payroll_deduction_id' => $this->payrollDeductionId,
            'type' => $this->type,
            'amount' => $this->amount,
            'transaction_date' => $this->transactionDate,
            'note' => $this->note,
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

    public function getEmployeeDebtInstallmentId(): ?int
    {
        return $this->employeeDebtInstallmentId;
    }

    public function getPayrollDeductionId(): ?int
    {
        return $this->payrollDeductionId;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getTransactionDate(): string
    {
        return $this->transactionDate;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setEmployeeDebtId(int $employeeDebtId): void
    {
        $this->employeeDebtId = $employeeDebtId;
    }

    public function setEmployeeDebtInstallmentId(?int $employeeDebtInstallmentId): void
    {
        $this->employeeDebtInstallmentId = $employeeDebtInstallmentId;
    }

    public function setPayrollDeductionId(?int $payrollDeductionId): void
    {
        $this->payrollDeductionId = $payrollDeductionId;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function setTransactionDate(string $transactionDate): void
    {
        $this->transactionDate = $transactionDate;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }
}
