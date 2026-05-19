<?php

declare(strict_types=1);

namespace App\Application\Branch\Command;

final readonly class UpdateBranchCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $code,
        public ?string $address,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?string $code = null,
        ?string $address = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            code: $code,
            address: $address,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}