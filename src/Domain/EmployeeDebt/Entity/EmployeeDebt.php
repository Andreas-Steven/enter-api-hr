<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebt\Entity;

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
use App\Domain\Shared\Enums\PaymentTerm;
use App\Domain\Shared\Enums\PaymentMethod;

final class EmployeeDebt
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'EmployeeDebt';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private int $employeeId,
        private int $deductionTypeId,
        private float $amount,
        private string $date,
        private ?string $note,
        private int $paymentTerm,
        private ?string $paymentMethod,
        private int $installmentCount,
        private float $installmentAmount,
        private float $remainingAmount,
        private float $paidAmount,
        private ?string $firstPaymentDate,
        private int $isSettled,
        private ?string $settledAt,
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
        float $amount,
        string $date,
        ?string $note,
        int $paymentTerm,
        ?string $paymentMethod,
        int $installmentCount,
        ?string $firstPaymentDate,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        $installmentAmount = self::calculateInstallmentAmount(
            amount: $amount,
            installmentCount: $paymentTerm === PaymentTerm::INSTALLMENT ? $installmentCount : 1
        );

        return new self(
            id: null,
            name: $name,
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            amount: $amount,
            date: $date,
            note: $note,
            paymentTerm: $paymentTerm,
            paymentMethod: $paymentMethod,
            installmentCount: $paymentTerm === PaymentTerm::INSTALLMENT ? $installmentCount : 1,
            installmentAmount: $installmentAmount,
            remainingAmount: $amount,
            paidAmount: 0.0,
            firstPaymentDate: $firstPaymentDate,
            isSettled: 0,
            settledAt: null,
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
        float $amount,
        string $date,
        ?string $note,
        int $paymentTerm,
        ?string $paymentMethod,
        int $installmentCount,
        float $installmentAmount,
        float $remainingAmount,
        float $paidAmount,
        ?string $firstPaymentDate,
        int $isSettled,
        ?string $settledAt,
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
            amount: $amount,
            date: $date,
            note: $note,
            paymentTerm: $paymentTerm,
            paymentMethod: $paymentMethod,
            installmentCount: $installmentCount,
            installmentAmount: $installmentAmount,
            remainingAmount: $remainingAmount,
            paidAmount: $paidAmount,
            firstPaymentDate: $firstPaymentDate,
            isSettled: $isSettled,
            settledAt: $settledAt,
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
            'amount' => $this->amount,
            'date' => $this->date,
            'note' => $this->note,
            'payment_term' => $this->paymentTerm,
            'payment_method' => $this->paymentMethod,
            'installment_count' => $this->installmentCount,
            'installment_amount' => $this->installmentAmount,
            'remaining_amount' => $this->remainingAmount,
            'paid_amount' => $this->paidAmount,
            'first_payment_date' => $this->firstPaymentDate,
            'is_settled' => $this->isSettled,
            'settled_at' => $this->settledAt,
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
     * Place EmployeeDebt-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */ 
    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function setEmployeeId(int $employeeId): void
    {
        $this->employeeId = $employeeId;
    }

    public function getDeductionTypeId(): int
    {
        return $this->deductionTypeId;
    }

    public function setDeductionTypeId(int $deductionTypeId): void
    {
        $this->deductionTypeId = $deductionTypeId;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        $this->amount = $amount;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): void
    {
        $this->date = $date;
    }

    public function getnote(): ?string
    {
        return $this->note;
    }

    public function setnote(?string $note): void
    {
        $this->note = $note;
    }

    public function getPaymentTerm(): int
    {
        return $this->paymentTerm;
    }

    public function updatePaymentTerm(int $paymentTerm): void
    {
        $this->paymentTerm = $paymentTerm;
    }

    public function updateInstallmentConfiguration(
        int $paymentTerm,
        ?string $paymentMethod,
        int $installmentCount,
        ?string $firstPaymentDate,
    ): void {
        $this->paymentTerm = $paymentTerm;
        $this->paymentMethod = $paymentMethod;
        $this->installmentCount = $paymentTerm === PaymentTerm::INSTALLMENT ? $installmentCount : 1;
        $this->firstPaymentDate = $firstPaymentDate;
        $this->installmentAmount = self::calculateInstallmentAmount(
            amount: $this->amount,
            installmentCount: $this->installmentCount
        );
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function updatePaymentMethod(?string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getInstallmentCount(): int
    {
        return $this->installmentCount;
    }

    public function updateInstallmentCount(int $installmentCount): void
    {
        $this->installmentCount = $installmentCount;
        $this->installmentAmount = self::calculateInstallmentAmount(
            amount: $this->amount,
            installmentCount: $this->installmentCount
        );
    }

    public function getInstallmentAmount(): float
    {
        return $this->installmentAmount;
    }

    public function getRemainingAmount(): float
    {
        return $this->remainingAmount;
    }

    public function getPaidAmount(): float
    {
        return $this->paidAmount;
    }

    public function recordPayment(float $amount): void
    {
        $this->paidAmount += $amount;
        $this->remainingAmount = max(0, $this->amount - $this->paidAmount);

        if ($this->remainingAmount <= 0) {
            $this->markAsSettled();
        }
    }

    public function reversePayment(float $amount): void
    {
        $this->paidAmount = max(0, $this->paidAmount - $amount);
        $this->remainingAmount = max(0, $this->amount - $this->paidAmount);
        $this->isSettled = 0;
        $this->settledAt = null;
    }

    public function adjustAmount(float $newAmount): void
    {
        $this->amount = $newAmount;
        $this->installmentAmount = self::calculateInstallmentAmount(
            amount: $this->amount,
            installmentCount: $this->installmentCount
        );
        $this->remainingAmount = max(0, $this->amount - $this->paidAmount);

        if ($this->remainingAmount <= 0) {
            $this->markAsSettled();
        }
    }

    public function getFirstPaymentDate(): ?string
    {
        return $this->firstPaymentDate;
    }

    public function updateFirstPaymentDate(?string $firstPaymentDate): void
    {
        $this->firstPaymentDate = $firstPaymentDate;
    }

    public function isSettled(): int
    {
        return $this->isSettled;
    }

    public function getSettledAt(): ?string
    {
        return $this->settledAt;
    }

    public function markAsSettled(): void
    {
        $this->isSettled = 1;
        $this->settledAt = (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private static function calculateInstallmentAmount(float $amount, int $installmentCount): float
    {
        if ($installmentCount <= 1) {
            return $amount;
        }

        return round($amount / $installmentCount, 2);
    }
}