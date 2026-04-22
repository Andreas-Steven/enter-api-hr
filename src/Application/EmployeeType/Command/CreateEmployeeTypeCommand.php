<?php

declare(strict_types=1);

namespace App\Application\EmployeeType\Command;

final readonly class CreateEmployeeTypeCommand
{
    public function __construct(
        public string $name,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}