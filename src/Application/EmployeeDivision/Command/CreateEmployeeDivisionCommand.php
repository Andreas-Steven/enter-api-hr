<?php

declare(strict_types=1);

namespace App\Application\EmployeeDivision\Command;

final readonly class CreateEmployeeDivisionCommand
{
    public function __construct(
        public string $name,
        public string $code,
        public int $status,
        public ?int $workPatternId = null,
        public ?array $detailInfo = null,
    ) {}

    public static function create(
        string $name,
        string $code,
        int $status,
        ?int $workPatternId = null,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            code: $code,
            status: $status,
            workPatternId: $workPatternId,
            detailInfo: $detailInfo,
        );
    }
}


