<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebt\Service;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\Enums\ApprovalStatus;

// Application Layer
use App\Application\EmployeeDebt\Dto\EmployeeDebtDetailInfo;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ConflictException;

/**
 * EmployeeDebt Domain Service
 *
 * Pure business logic and domain rules.
 *
 * EmployeeDebt is a standalone module that only records debts for
 * PayrollDeductionType with CreatesDebt::YES. Payment tracking is manual
 * (paid_amount / remaining_amount / is_settled). There is no installment
 * system and no auto-generation of PayrollDeduction records.
 *
 * Aturan utama:
 * - amount harus > 0 saat create
 * - amount baru harus > 0 dan >= paid_amount saat update
 *   (tidak boleh menurunkan principal di bawah yang sudah dibayar)
 */
final class EmployeeDebtDomainService
{
    use DomainValidator;

    public function __construct(
        private EmployeeDebtRepositoryInterface $repository,
    ) {}

    /**
     * Guard: EmployeeDebt hanya boleh dibuat dengan PayrollDeductionType
     * yang memiliki CreatesDebt::YES. Tolak jika tidak.
     */
    public function guardCreatesDebt(EmployeeDebtDetailInfo $debtInfo): void
    {
        if (!$debtInfo->createsDebt()) {
            $deductionType = $debtInfo->getDeductionType();

            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt.deduction_type_not_creates_debt',
                    params: [
                        'id' => $deductionType['id'] ?? null,
                        'name' => $deductionType['name'] ?? null,
                    ],
                    domain: 'validation'
                )
            );
        }
    }

    /**
     * Guard: validasi amount saat create.
     * amount harus > 0.
     */
    public function guardCreateAmount(int $amount): void
    {
        if ($amount <= 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt.amount_must_be_positive',
                    params: ['amount' => $amount],
                    domain: 'validation'
                )
            );
        }
    }

    /**
     * Guard: validasi amount saat update.
     * amount baru harus > 0 dan tidak boleh kurang dari paid_amount
     * (tidak boleh menurunkan principal di bawah yang sudah dibayar).
     */
    public function guardUpdateAmount(float $newAmount, float $paidAmount): void
    {
        if ($newAmount <= 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt.amount_must_be_positive',
                    params: ['amount' => $newAmount],
                    domain: 'validation'
                )
            );
        }

        if ($newAmount < $paidAmount) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt.amount_below_paid',
                    params: [
                        'amount' => $newAmount,
                        'paid' => $paidAmount,
                    ],
                    domain: 'validation'
                )
            );
        }
    }

    /**
     * Guard that approval operations require active status
     */
    public function guardApprovalRequiresActiveStatus(EmployeeDebt $entity): void
    {
        if (!$entity->isActive()) {
            throw new ConflictException(
                translate: Message::create(
                    key: 'resource.update_not_allowed_by_status',
                    params: [
                        'resource' => $entity->getResource(),
                        'current_status' => $entity->getStatus()->label(),
                    ]
                )
            );
        }

        return;
    }

    /**
     * Guard approval status transition
     */
    public function guardApprovalTransition(ApprovalStatus $currentStatus, ApprovalStatus $newStatus): void
    {
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
    }
}
