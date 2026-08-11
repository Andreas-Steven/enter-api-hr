<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebtTransaction\Service;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;
use App\Domain\EmployeeDebtTransaction\Repository\EmployeeDebtTransactionRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\Enums\ApprovalStatus;
use App\Domain\Shared\Enums\TransactionType;
use App\Domain\Shared\Enums\PaymentSource;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ConflictException;

/**
 * EmployeeDebtTransaction Domain Service
 *
 * Pure business logic and domain rules untuk transaksi hutang karyawan.
 *
 * Aturan utama:
 * - transaction_type harus salah satu: payment|adjust_plus|adjust_minus
 * - payment_source wajib jika transaction_type = payment
 * - payment_source harus null jika transaction_type = adjust_plus|adjust_minus
 *   (arah koreksi ditentukan oleh transaction_type, amount selalu positif)
 * - payroll_deduction_id & payroll_period wajib jika payment_source = payroll
 * - Tidak boleh ada duplikat transaksi untuk PayrollDeduction yang sama
 */
final class EmployeeDebtTransactionDomainService
{
    use DomainValidator;

    public function __construct(
        private EmployeeDebtTransactionRepositoryInterface $repository
    ) {}

    /**
     * Validasi saat create transaksi manual.
     */
    public function guardCreate(
        string $transactionType,
        ?string $paymentSource,
        ?int $payrollDeductionId,
        ?int $payrollPeriod,
        int $amount,
    ): void {
        $this->guardTransactionType($transactionType);
        $this->guardPaymentSource($transactionType, $paymentSource);
        $this->guardPayrollLink($paymentSource, $payrollDeductionId, $payrollPeriod);
        $this->guardAmount($transactionType, $amount);

        if ($payrollDeductionId !== null) {
            if ($this->repository->existsForPayrollDeduction($payrollDeductionId)) {
                throw new ConflictException(
                    translate: Message::create(
                        key: 'employee_debt_transaction.payroll_deduction_already_linked',
                        domain: 'validation',
                        params: ['payroll_deduction_id' => $payrollDeductionId]
                    )
                );
            }
        }
    }

    /**
     * Guard rules:
     * - payment: debt harus Approved (approved=1) DAN Active
     * - adjust_plus/adjust_minus: debt harus BELUM Approved (approved=null/0)
     *   DAN status harus DRAFT atau ACTIVE
     */
    public function guardDebtApprovedAndActive(EmployeeDebt $debt, string $transactionType): void
    {
        $approved = $debt->getApproved();
        $approval = ApprovalStatus::tryFrom($approved ?? ApprovalStatus::PENDING->value);
        $isApproved = $approval === ApprovalStatus::APPROVED;

        if (TransactionType::isAdjustment($transactionType)) {
            // Adjustment hanya boleh jika debt BELUM di-approve
            if ($isApproved) {
                throw new ConflictException(
                    translate: Message::create(
                        key: 'employee_debt_transaction.debt_already_approved_no_adjust',
                        domain: 'validation',
                        params: [
                            'id' => $debt->getId(),
                        ]
                    )
                );
            }

            // Status harus DRAFT atau ACTIVE
            $status = $debt->getStatus();
            if (!$status->isDraft() && !$status->isActive()) {
                throw new ConflictException(
                    translate: Message::create(
                        key: 'resource.update_not_allowed_by_status',
                        params: [
                            'resource' => $debt->getResource(),
                            'current_status' => $status->label(),
                        ]
                    )
                );
            }
        } else {
            // Payment: harus Approved DAN Active
            if (!$isApproved) {
                throw new ConflictException(
                    translate: Message::create(
                        key: 'employee_debt_transaction.debt_not_approved',
                        domain: 'validation',
                        params: [
                            'id' => $debt->getId(),
                            'approval_status' => $approval->getLabel(),
                        ]
                    )
                );
            }

            if (!$debt->isActive()) {
                throw new ConflictException(
                    translate: Message::create(
                        key: 'resource.update_not_allowed_by_status',
                        params: [
                            'resource' => $debt->getResource(),
                            'current_status' => $debt->getStatus()->label(),
                        ]
                    )
                );
            }
        }
    }

    /**
     * Validasi saat update transaksi.
     */
    public function guardUpdate(
        EmployeeDebtTransaction $entity,
        ?string $transactionType,
        ?string $paymentSource,
        ?int $payrollDeductionId,
        ?int $payrollPeriod,
        ?int $amount,
    ): void {
        $resolvedType = $transactionType ?? $entity->getTransactionType();
        $resolvedSource = $paymentSource ?? $entity->getPaymentSource();
        $resolvedDeductionId = $payrollDeductionId ?? $entity->getPayrollDeductionId();
        $resolvedPeriod = $payrollPeriod ?? $entity->getPayrollPeriod();
        $resolvedAmount = $amount ?? $entity->getAmount();

        $this->guardTransactionType($resolvedType);
        $this->guardPaymentSource($resolvedType, $resolvedSource);
        $this->guardPayrollLink($resolvedSource, $resolvedDeductionId, $resolvedPeriod);
        $this->guardAmount($resolvedType, $resolvedAmount);

        if ($resolvedDeductionId !== null && $resolvedDeductionId !== $entity->getPayrollDeductionId()) {
            if ($this->repository->existsForPayrollDeduction($resolvedDeductionId)) {
                throw new ConflictException(
                    translate: Message::create(
                        key: 'employee_debt_transaction.payroll_deduction_already_linked',
                        domain: 'validation',
                        params: ['payroll_deduction_id' => $resolvedDeductionId]
                    )
                );
            }
        }
    }

    private function guardTransactionType(string $type): void
    {
        if (!in_array($type, TransactionType::ALL, true)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt_transaction.invalid_transaction_type',
                    domain: 'validation',
                    params: ['type' => $type]
                )
            );
        }
    }

    private function guardPaymentSource(string $type, ?string $source): void
    {
        if (TransactionType::isInflow($type) && empty($source)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt_transaction.payment_source_required',
                    domain: 'validation',
                    params: []
                )
            );
        }

        if (TransactionType::isAdjustment($type) && $source !== null) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt_transaction.payment_source_must_be_null',
                    domain: 'validation',
                    params: []
                )
            );
        }

        if ($source !== null && !in_array($source, PaymentSource::ALL, true)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt_transaction.invalid_payment_source',
                    domain: 'validation',
                    params: ['source' => $source]
                )
            );
        }
    }

    private function guardPayrollLink(?string $source, ?int $deductionId, ?int $period): void
    {
        if ($source === PaymentSource::PAYROLL) {
            if ($deductionId === null) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'employee_debt_transaction.payroll_deduction_id_required',
                        domain: 'validation',
                        params: []
                    )
                );
            }

            if ($period === null) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'employee_debt_transaction.payroll_period_required',
                        domain: 'validation',
                        params: []
                    )
                );
            }
        }
    }

    private function guardAmount(string $type, int $amount): void
    {
        // Semua transaction type (payment, adjust_plus, adjust_minus) wajib
        // amount > 0. Arah koreksi ditentukan oleh transaction_type, bukan
        // tanda amount.
        if ($amount <= 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.min_value',
                    domain: 'validation',
                    params: ['field' => 'amount', 'min' => 0]
                )
            );
        }
    }
}
