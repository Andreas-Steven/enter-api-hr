<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebt\Command;

use App\Domain\Shared\Enums\PaymentTerm;

final readonly class CreateEmployeeDebtCommand
{
    public function __construct(
        public int $employeeId,
        public int $deductionTypeId,
        public float $amount,
        public string $date,
        public ?string $note,
        public int $status,
        public int $paymentTerm,
        public ?string $paymentMethod,
        public int $installmentCount,
        public ?string $firstPaymentDate,
        public ?array $detailInfo,
    ) {}

    public static function create(
        int $employeeId,
        int $deductionTypeId,
        float $amount,
        string $date,
        ?string $note,
        int $status,
        int $paymentTerm = PaymentTerm::FULL,
        ?string $paymentMethod = null,
        int $installmentCount = 1,
        ?string $firstPaymentDate = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            employeeId: $employeeId,
            deductionTypeId: $deductionTypeId,
            amount: $amount,
            date: $date,
            note: $note,
            status: $status,
            paymentTerm: $paymentTerm,
            paymentMethod: $paymentMethod,
            installmentCount: $installmentCount,
            firstPaymentDate: $firstPaymentDate,
            detailInfo: $detailInfo,
        );
    }
}