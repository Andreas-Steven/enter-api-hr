<?php

declare(strict_types=1);

namespace App\Application\WorkTime\Command;

final readonly class UpdateWorkTimeCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?array $workingDays,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?array $workingDays = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            workingDays: $workingDays,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}