<?php

declare(strict_types=1);

namespace App\Application\EmployeeDebt\Command;

use App\Domain\Shared\Enums\PaymentTerm;

final readonly class UpdateEmployeeDebtCommand
{
    public function __construct(
        public int $id,
        public ?int $employeeId,
        public ?int $deductionTypeId,
        public ?float $amount,
        public ?string $date,
        public ?string $note,
        public ?int $status,
        public ?int $paymentTerm,
        public ?string $paymentMethod,
        public ?int $installmentCount,
        public ?string $firstPaymentDate,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?int $employeeId = null,
        ?int $deductionTypeId = null,
        ?float $amount = null,
        ?string $date = null,
        ?string $note = null,
        ?int $status = null,
        ?int $paymentTerm = PaymentTerm::FULL,
        ?string $paymentMethod = null,
        ?int $installmentCount = null,
        ?string $firstPaymentDate = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
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
            lockVersion: $lockVersion,
        );
    }
}