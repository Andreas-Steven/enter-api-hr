<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Transaction type for EmployeeDebtTransaction.
 *
 * - PAYMENT: pembayaran masuk (cash/transfer/payroll) yang menambah paid_amount
 * - ADJUST_PLUS: koreksi manual yang menambah paid_amount (tanpa uang fisik).
 * - ADJUST_MINUS: koreksi manual yang mengurangi paid_amount (tanpa uang fisik).
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
     * Apakah transaction type ini koreksi positif (menambah paid_amount)?
     */
    public static function isAdjustPlus(string $type): bool
    {
        return $type === self::ADJUST_PLUS;
    }

    /**
     * Apakah transaction type ini koreksi negatif (mengurangi paid_amount)?
     */
    public static function isAdjustMinus(string $type): bool
    {
        return $type === self::ADJUST_MINUS;
    }
}
