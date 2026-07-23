<?php

declare(strict_types=1);

namespace App\Application\DeductionType\Command;

final readonly class CreateDeductionTypeCommand
{
    public function __construct(
        public string $name,
        public string $category,
        public int $createsDebt,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        string $category,
        int $createsDebt,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            category: $category,
            createsDebt: $createsDebt,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}