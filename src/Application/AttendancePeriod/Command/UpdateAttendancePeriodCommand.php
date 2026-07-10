<?php

declare(strict_types=1);

namespace App\Application\AttendancePeriod\Command;

use DateTimeImmutable;

final readonly class UpdateAttendancePeriodCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $startDate,
        public ?string $endDate,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?string $startDate = null,
        ?string $endDate = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            startDate: $startDate,
            endDate: $endDate,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}