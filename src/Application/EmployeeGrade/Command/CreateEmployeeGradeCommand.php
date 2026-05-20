<?php

declare(strict_types=1);

namespace App\Application\EmployeeGrade\Command;

final readonly class CreateEmployeeGradeCommand
{
    public function __construct(
        public string $name,
        public int $level,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        int $level,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            level: $level,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}