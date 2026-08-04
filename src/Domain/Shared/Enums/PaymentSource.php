<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Payment source for EmployeeDebtTransaction.
 *
 * Hanya relevan ketika transaction_type = payment.
 * Saat transaction_type = adjustment, payment_source harus null.
 *
 * - PAYROLL: potongan gaji otomatis via PayrollDeduction
 *   (wajib melampirkan payroll_deduction_id & payroll_period)
 * - NON_PAYROLL: pembayaran non-payroll (mis. transfer bank/e-wallet)
 * - MANUAL: pembayaran manual (mis. tunai) yang dicatat oleh admin
 */
final class PaymentSource
{
    public const PAYROLL = 'payroll';
    public const NON_PAYROLL = 'non_payroll';
    public const MANUAL = 'manual';

    public const ALL = [
        self::PAYROLL,
        self::NON_PAYROLL,
        self::MANUAL,
    ];
}
