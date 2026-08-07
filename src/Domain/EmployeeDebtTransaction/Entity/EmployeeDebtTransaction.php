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
        private int $employeeId,
        private string $transactionType,
        private string $date,
        private int $amount,
        private ?string $paymentSource,
        private ?int $payrollDeductionId,
        private ?int $payrollPeriod,
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
        int $employeeId,
        string $transactionType,
        string $date,
        int $amount,
        ?string $paymentSource,
        ?int $payrollDeductionId,
        ?int $payrollPeriod,
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
            employeeId: $employeeId,
            transactionType: $transactionType,
            date: $date,
            amount: $amount,
            paymentSource: $paymentSource,
            payrollDeductionId: $payrollDeductionId,
            payrollPeriod: $payrollPeriod,
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
        int $employeeId,
        string $transactionType,
        string $date,
        int $amount,
        ?string $paymentSource,
        ?int $payrollDeductionId,
        ?int $payrollPeriod,
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
            employeeId: $employeeId,
            transactionType: $transactionType,
            date: $date,
            amount: $amount,
            paymentSource: $paymentSource,
            payrollDeductionId: $payrollDeductionId,
            payrollPeriod: $payrollPeriod,
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
            'employee_id' => $this->employeeId,
            'transaction_type' => $this->transactionType,
            'date' => $this->date,
            'amount' => $this->amount,
            'payment_source' => $this->paymentSource,
            'payroll_deduction_id' => $this->payrollDeductionId,
            'payroll_period' => $this->payrollPeriod,
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

    public function setEmployeeDebtId(int $employeeDebtId): void
    {
        $this->employeeDebtId = $employeeDebtId;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function setEmployeeId(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function getTransactionType(): string
    {
        return $this->transactionType;
    }

    public function setTransactionType(string $transactionType): void
    {
        $this->transactionType = $transactionType;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    public function getPaymentSource(): ?string
    {
        return $this->paymentSource;
    }

    public function setPaymentSource(?string $paymentSource): void
    {
        $this->paymentSource = $paymentSource;
    }

    public function getPayrollDeductionId(): ?int
    {
        return $this->payrollDeductionId;
    }

    public function setPayrollDeductionId(?int $payrollDeductionId): void
    {
        $this->payrollDeductionId = $payrollDeductionId;
    }

    public function getPayrollPeriod(): ?int
    {
        return $this->payrollPeriod;
    }

    public function setPayrollPeriod(?int $payrollPeriod): void
    {
        $this->payrollPeriod = $payrollPeriod;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }
}
