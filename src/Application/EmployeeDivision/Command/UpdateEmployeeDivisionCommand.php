<?php

declare(strict_types=1);

namespace App\Application\EmployeeDivision\Command;

final readonly class UpdateEmployeeDivisionCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $code,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?string $code = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            code: $code,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}