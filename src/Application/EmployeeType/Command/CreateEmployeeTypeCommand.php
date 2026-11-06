<?php

declare(strict_types=1);

namespace App\Application\EmployeeType\Command;

final readonly class CreateEmployeeTypeCommand
{
    public function __construct(
        public string $name,
        public int $status,
        public int $isContract,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        int $status,
        int $isContract = 0,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            status: $status,
            isContract: $isContract,
            detailInfo: $detailInfo,
        );
    }
}