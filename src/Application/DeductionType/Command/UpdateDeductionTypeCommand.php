<?php

declare(strict_types=1);

namespace App\Application\DeductionType\Command;

final readonly class UpdateDeductionTypeCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $category,
        public ?int $createsLoan,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?string $category = null,
        ?int $createsLoan = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            category: $category,
            createsLoan: $createsLoan,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}