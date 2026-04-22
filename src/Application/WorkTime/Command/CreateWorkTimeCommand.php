<?php

declare(strict_types=1);

namespace App\Application\WorkTime\Command;

final readonly class CreateWorkTimeCommand
{
    public function __construct(
        public string $name,
        public array $workingDays,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        array $workingDays,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            workingDays: $workingDays,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}