<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Tipe transaksi pada employee_debt_transaction (ledger hutang karyawan).
 *
 * - PAYMENT:    Uang masuk untuk melunasi hutang (cash/transfer/payroll_deduction).
 * - ADJUSTMENT: Koreksi saldo hutang (mis. salah catat nominal).
 * - SETTLEMENT: Dianggap lunas tanpa pembayaran penuh (mis. kompensasi/komitmen).
 * - REVERSAL:   Pembatalan transaksi sebelumnya (rollback paid_amount).
 */
final class TransactionType
{
    public const PAYMENT = 'payment';
    public const ADJUSTMENT = 'adjustment';
    public const SETTLEMENT = 'settlement';
    public const REVERSAL = 'reversal';

    /**
     * Tipe yang menambah paid_amount (memotong sisa hutang).
     */
    public static function creditTypes(): array
    {
        return [self::PAYMENT, self::SETTLEMENT];
    }

    /**
     * Tipe yang mengurangi paid_amount (mengembalikan sisa hutang).
     */
    public static function debitTypes(): array
    {
        return [self::REVERSAL];
    }

    /**
     * Tipe yang mengubah nominal tanpa arah kas (koreksi).
     */
    public static function neutralTypes(): array
    {
        return [self::ADJUSTMENT];
    }

    public static function all(): array
    {
        return [self::PAYMENT, self::ADJUSTMENT, self::SETTLEMENT, self::REVERSAL];
    }

    public static function isValid(string $type): bool
    {
        return in_array($type, self::all(), true);
    }

    public static function isCredit(string $type): bool
    {
        return in_array($type, self::creditTypes(), true);
    }

    public static function isDebit(string $type): bool
    {
        return in_array($type, self::debitTypes(), true);
    }
}
