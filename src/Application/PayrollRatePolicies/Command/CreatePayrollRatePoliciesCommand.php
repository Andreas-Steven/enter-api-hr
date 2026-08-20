<?php

declare(strict_types=1);

namespace App\Application\PayrollRatePolicies\Command;

final readonly class CreatePayrollRatePoliciesCommand
{
    public function __construct(
        public string $name,
        public string $rateType,
        public ?int $deductionTypeId,
        public int $amount,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        string $rateType,
        ?int $deductionTypeId,
        int $amount,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            rateType: $rateType,
            deductionTypeId: $deductionTypeId,
            amount: $amount,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}
