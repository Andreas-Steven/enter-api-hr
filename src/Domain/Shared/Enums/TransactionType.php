<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Transaction type for EmployeeDebtTransaction.
 *
 * - PAYMENT: pembayaran manual (cash/transfer) oleh karyawan
 * - PAYROLL: potongan gaji otomatis via PayrollDeduction
 * - ADJUSTMENT: koreksi manual (+/-) tanpa uang fisik
 * - REVERSAL: pembatalan transaksi sebelumnya
 */
final class TransactionType
{
    public const PAYMENT = 'payment';
    public const PAYROLL = 'payroll';
    public const ADJUSTMENT = 'adjustment';
    public const REVERSAL = 'reversal';

    public const ALL = [
        self::PAYMENT,
        self::PAYROLL,
        self::ADJUSTMENT,
        self::REVERSAL,
    ];

    /**
     * Apakah transaction type ini menambah paid_amount (pembayaran masuk)?
     */
    public static function isInflow(string $type): bool
    {
        return $type === self::PAYMENT || $type === self::PAYROLL;
    }

    /**
     * Apakah transaction type ini mengurangi paid_amount (pembatalan/koreksi negatif)?
     */
    public static function isOutflow(string $type): bool
    {
        return $type === self::REVERSAL;
    }
}
