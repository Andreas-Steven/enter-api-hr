<?php

declare(strict_types=1);

namespace App\Application\DeductionType\Command;

final readonly class CreateDeductionTypeCommand
{
    public function __construct(
        public string $name,
        public string $category,
        public int $createsLoan,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        string $category,
        int $createsLoan,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            category: $category,
            createsLoan: $createsLoan,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}