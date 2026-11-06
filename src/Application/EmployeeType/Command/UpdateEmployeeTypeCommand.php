<?php

declare(strict_types=1);

namespace App\Application\EmployeeType\Command;

final readonly class UpdateEmployeeTypeCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $status,
        public ?int $isContract,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?int $status = null,
        ?int $isContract = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            status: $status,
            isContract: $isContract,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}