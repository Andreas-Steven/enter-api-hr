<?php

declare(strict_types=1);

namespace App\Application\EmployeeDivision\Command;

final readonly class CreateEmployeeDivisionCommand
{
    public function __construct(
        public string $name,
        public string $code,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        string $code,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            code: $code,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}