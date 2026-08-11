<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Transaction type for EmployeeDebtTransaction.
 *
 * - PAYMENT: pembayaran masuk (cash/transfer/payroll) yang menambah paid_amount,
 *   sehingga mengurangi remaining_amount.
 * - ADJUST_PLUS: koreksi manual yang menambah pokok hutang (amount & remaining_amount),
 *   tanpa mempengaruhi paid_amount. Contoh: "nambah hutang".
 * - ADJUST_MINUS: koreksi manual yang mengurangi pokok hutang (amount & remaining_amount),
 *   tanpa mempengaruhi paid_amount. Contoh: "kurangi hutang".
 *   Arah koreksi ditentukan oleh transaction_type, bukan tanda amount.
 *   Saat ADJUST_PLUS / ADJUST_MINUS, payment_source harus null dan amount
 *   harus selalu positif (> 0).
 */
final class TransactionType
{
    public const PAYMENT = 'payment';
    public const ADJUST_PLUS = 'adjust_plus';
    public const ADJUST_MINUS = 'adjust_minus';

    public const ALL = [
        self::PAYMENT,
        self::ADJUST_PLUS,
        self::ADJUST_MINUS,
    ];

    /**
     * Apakah transaction type ini menambah paid_amount (pembayaran masuk)?
     */
    public static function isInflow(string $type): bool
    {
        return $type === self::PAYMENT;
    }

    /**
     * Apakah transaction type ini koreksi manual (plus atau minus)?
     */
    public static function isAdjustment(string $type): bool
    {
        return $type === self::ADJUST_PLUS || $type === self::ADJUST_MINUS;
    }

    /**
     * Apakah transaction type ini koreksi positif (menambah pokok hutang)?
     */
    public static function isAdjustPlus(string $type): bool
    {
        return $type === self::ADJUST_PLUS;
    }

    /**
     * Apakah transaction type ini koreksi negatif (mengurangi pokok hutang)?
     */
    public static function isAdjustMinus(string $type): bool
    {
        return $type === self::ADJUST_MINUS;
    }
}
