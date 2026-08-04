<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

/**
 * Payment source for EmployeeDebtTransaction.
 *
 * - CASH: pembayaran tunai
 * - TRANSFER: pembayaran via transfer bank
 * - PAYROLL_DEDUCTION: potong gaji via PayrollDeduction
 */
final class PaymentSource
{
    public const CASH = 'cash';
    public const TRANSFER = 'transfer';
    public const PAYROLL_DEDUCTION = 'payroll_deduction';

    public const ALL = [
        self::CASH,
        self::TRANSFER,
        self::PAYROLL_DEDUCTION,
    ];
}
