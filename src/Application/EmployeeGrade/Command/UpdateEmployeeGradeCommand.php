<?php

declare(strict_types=1);

namespace App\Application\EmployeeGrade\Command;

final readonly class UpdateEmployeeGradeCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $level,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?int $level = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            level: $level,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}