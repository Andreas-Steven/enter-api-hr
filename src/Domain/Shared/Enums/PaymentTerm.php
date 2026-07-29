<?php

declare(strict_types=1);

namespace App\Domain\Shared\Enums;

final class PaymentTerm
{
    public const FULL = 1;
    public const INSTALLMENT = 2;
}