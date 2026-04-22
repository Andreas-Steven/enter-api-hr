<?php

declare(strict_types=1);

namespace App\Application\GeneralAffair\Command;

final readonly class CreateGeneralAffairCommand
{
    public function __construct(
        public string $name,
        public int $productId,
        public int $categoryId,
        public int $locationId,
        public ?array $asset,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        int $productId,
        int $categoryId,
        int $locationId,
        ?array $asset,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            productId: $productId,
            categoryId: $categoryId,
            locationId: $locationId,
            asset: $asset,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}