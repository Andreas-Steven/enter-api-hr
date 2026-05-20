<?php

declare(strict_types=1);

namespace App\Application\Branch\Command;

final readonly class CreateBranchCommand
{
    public function __construct(
        public string $name,
        public string $code,
        public string $address,
        public int $status,
        public int $branchId,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        string $code,
        string $address,
        int $status,
        int $branchId,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            code: $code,
            address: $address,
            status: $status,
            branchId: $branchId,
            detailInfo: $detailInfo,
        );
    }
}