<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebtTransaction\Service;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;
use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;
use App\Domain\EmployeeDebtTransaction\Repository\EmployeeDebtTransactionRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\Enums\TransactionType;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ConflictException;

/**
 * EmployeeDebtTransaction Domain Service
 *
 * Pure business logic and domain rules untuk ledger transaksi hutang.
 * Bertanggung jawab atas validasi invariant saat apply payment/settlement/
 * adjustment/reversal, termasuk konsistensi saldo employee_debt.
 */
final class EmployeeDebtTransactionDomainService
{
    use DomainValidator;

    public function __construct(
        private EmployeeDebtTransactionRepositoryInterface $repository
    ) {}

    /**
     * Validasi invariant saat create transaction baru.
     *
     * Aturan:
     * - type wajib valid (payment|adjustment|settlement|reversal)
     * - amount > 0
     * - employee_debt_id wajib > 0
     * - Untuk PAYMENT: amount tidak boleh melebihi remaining_amount debt
     * - Untuk REVERSAL: amount tidak boleh melebihi paid_amount debt
     * - Untuk PAYMENT/REVERSAL dengan installment_id: installment harus milik debt yang sama
     * - Untuk PAYMENT dengan installment_id: installment harus pending (payment_status=0)
     */
    public function guardCreate(
        EmployeeDebt $debt,
        ?EmployeeDebtInstallment $installment,
        string $type,
        float $amount,
    ): void {
        if (!TransactionType::isValid($type)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt_transaction.invalid_type',
                    domain: 'validation',
                    params: ['type' => $type]
                )
            );
        }

        if ($amount <= 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.min_value',
                    domain: 'validation',
                    params: ['field' => 'amount', 'min' => 0]
                )
            );
        }

        if ($installment !== null) {
            if ($installment->getEmployeeDebtId() !== $debt->getId()) {
                throw new ConflictException(
                    translate: Message::create(
                        key: 'employee_debt_transaction.installment_debt_mismatch',
                        domain: 'validation',
                        params: [
                            'installment_id' => $installment->getId(),
                            'employee_debt_id' => $debt->getId(),
                        ]
                    )
                );
            }
        }

        if ($type === TransactionType::PAYMENT) {
            if ($amount > $debt->getRemainingAmount()) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'employee_debt_transaction.payment_exceeds_remaining',
                        domain: 'validation',
                        params: [
                            'amount' => $amount,
                            'remaining' => $debt->getRemainingAmount(),
                        ]
                    )
                );
            }

            if ($installment !== null && $installment->isPaid()) {
                throw new ConflictException(
                    translate: Message::create(
                        key: 'employee_debt_transaction.installment_already_paid',
                        domain: 'validation',
                        params: ['installment_id' => $installment->getId()]
                    )
                );
            }
        }

        if ($type === TransactionType::REVERSAL) {
            if ($amount > $debt->getPaidAmount()) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'employee_debt_transaction.reversal_exceeds_paid',
                        domain: 'validation',
                        params: [
                            'amount' => $amount,
                            'paid' => $debt->getPaidAmount(),
                        ]
                    )
                );
            }
        }
    }

    /**
     * Validasi invariant saat reversal transaction tertentu.
     *
     * - Transaction asli harus ada & tidak deleted
     * - Tidak boleh reversal dari reversal
     * - Amount reversal tidak boleh melebihi amount transaction asli
     */
    public function guardReversal(
        EmployeeDebtTransaction $original,
        float $reversalAmount,
    ): void {
        if ($original->getType() === TransactionType::REVERSAL) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt_transaction.cannot_reverse_reversal',
                    domain: 'validation',
                    params: ['id' => $original->getId()]
                )
            );
        }

        if ($reversalAmount > $original->getAmount()) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'employee_debt_transaction.reversal_exceeds_original',
                    domain: 'validation',
                    params: [
                        'reversal' => $reversalAmount,
                        'original' => $original->getAmount(),
                    ]
                )
            );
        }
    }

    /**
     * Hitung signed effect dari transaction terhadap paid_amount debt.
     * - PAYMENT/SETTLEMENT: +amount (memotong sisa hutang)
     * - REVERSAL: -amount (mengembalikan sisa hutang)
     * - ADJUSTMENT: 0 (koreksi nominal, ditangani terpisah)
     */
    public static function signedEffect(string $type, float $amount): float
    {
        return match ($type) {
            TransactionType::PAYMENT, TransactionType::SETTLEMENT => $amount,
            TransactionType::REVERSAL => -$amount,
            TransactionType::ADJUSTMENT => 0.0,
            default => 0.0,
        };
    }
}
