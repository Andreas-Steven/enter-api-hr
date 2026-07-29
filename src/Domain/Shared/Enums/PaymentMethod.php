<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

final class PaymentMethod
{
    public const CASH = 'cash';
    public const TRANSFER = 'transfer';
    public const PAYROLL_DEDUCTION = 'payroll_deduction';
    public const ADJUSTMENT = 'adjustment';
}
