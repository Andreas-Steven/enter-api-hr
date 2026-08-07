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
use App\Domain\Shared\Enums\ApprovalStatus;

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
        private int $amount,
        private string $date,
        private ?string $note,
        private ?int $approved,
        private ?string $approvedNote,
        private int $remainingAmount,
        private int $paidAmount,
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
        int $amount,
        string $date,
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
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            amount: $amount,
            date: $date,
            note: $note,
            approved: null,
            approvedNote: null,
            remainingAmount: $amount,
            paidAmount: 0,
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
        int $amount,
        string $date,
        ?string $note,
        ?int $approved,
        ?string $approvedNote,
        int $remainingAmount,
        int $paidAmount,
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
            approved: $approved,
            approvedNote: $approvedNote,
            remainingAmount: $remainingAmount,
            paidAmount: $paidAmount,
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
            'approved' => $this->approved,
            'approved_note' => $this->approvedNote,
            'remaining_amount' => $this->remainingAmount,
            'paid_amount' => $this->paidAmount,
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

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): void
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): void
    {
        $this->note = $note;
    }

    public function getRemainingAmount(): int
    {
        return $this->remainingAmount;
    }

    public function getPaidAmount(): int
    {
        return $this->paidAmount;
    }

    public function recordPayment(int $amount): void
    {
        if ($amount <= 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt.payment_must_be_positive',
                    params: ['amount' => $amount]
                )
            );
        }

        if ($amount > $this->remainingAmount) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt.payment_exceeds_remaining',
                    params: [
                        'amount' => $amount,
                        'remaining' => $this->remainingAmount,
                    ]
                )
            );
        }

        $this->paidAmount += $amount;
        $this->remainingAmount = $this->amount - $this->paidAmount;

        if ($this->remainingAmount <= 0) {
            $this->markAsSettled();
        }
    }

    public function reversePayment(int $amount): void
    {
        if ($amount <= 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt.reversal_must_be_positive',
                    params: ['amount' => $amount]
                )
            );
        }

        if ($amount > $this->paidAmount) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt.reversal_exceeds_paid',
                    params: [
                        'amount' => $amount,
                        'paid' => $this->paidAmount,
                    ]
                )
            );
        }

        $this->paidAmount -= $amount;
        $this->remainingAmount = $this->amount - $this->paidAmount;
        $this->isSettled = 0;
        $this->settledAt = null;
    }

    public function adjustAmount(int $newAmount): void
    {
        if ($newAmount <= 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt.amount_must_be_positive',
                    params: ['amount' => $newAmount]
                )
            );
        }

        if ($newAmount < $this->paidAmount) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt.amount_below_paid',
                    params: [
                        'amount' => $newAmount,
                        'paid' => $this->paidAmount,
                    ]
                )
            );
        }

        $this->amount = $newAmount;
        $this->remainingAmount = $this->amount - $this->paidAmount;

        if ($this->remainingAmount <= 0) {
            $this->markAsSettled();
        }
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

    public function getApproved(): ?int
    {
        return $this->approved;
    }

    public function getApprovedNote(): ?string
    {
        return $this->approvedNote;
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
                    key: 'employee_debt.invalid_approval_transition',
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
}
