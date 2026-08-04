<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Transaction type for EmployeeDebtTransaction.
 *
 * - PAYMENT: pembayaran masuk (cash/transfer/payroll) yang menambah paid_amount
 * - ADJUSTMENT: koreksi manual (+/-) tanpa uang fisik.
 *   Arah koreksi ditentukan oleh tanda amount (plus = menambah, minus = mengurangi).
 *   Saat ADJUSTMENT, payment_source harus null.
 */
final class TransactionType
{
    public const PAYMENT = 'payment';
    public const ADJUSTMENT = 'adjustment';

    public const ALL = [
        self::PAYMENT,
        self::ADJUSTMENT,
    ];

    /**
     * Apakah transaction type ini menambah paid_amount (pembayaran masuk)?
     */
    public static function isInflow(string $type): bool
    {
        return $type === self::PAYMENT;
    }

    /**
     * Apakah transaction type ini koreksi manual (bisa plus atau minus)?
     */
    public static function isAdjustment(string $type): bool
    {
        return $type === self::ADJUSTMENT;
    }
}
