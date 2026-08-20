<?php

declare(strict_types=1);

namespace App\Application\PayrollRatePolicies\Command;

final readonly class UpdatePayrollRatePoliciesCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $rateType,
        public ?int $deductionTypeId,
        public ?int $amount,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?string $rateType = null,
        ?int $deductionTypeId = null,
        ?int $amount = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            rateType: $rateType,
            deductionTypeId: $deductionTypeId,
            amount: $amount,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}
