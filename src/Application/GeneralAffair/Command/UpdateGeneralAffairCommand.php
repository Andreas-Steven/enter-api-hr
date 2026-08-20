<?php

declare(strict_types=1);

namespace App\Application\GeneralAffair\Command;

final readonly class UpdateGeneralAffairCommand
{
    public function __construct(
        public int $id,
        /* public ?string $name, */
        public ?int $productId,
        public ?int $categoryId,
        public ?int $locationId,
        public ?array $asset,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        /* ?string $name = null, */
        ?int $productId = null,
        ?int $categoryId = null,
        ?int $locationId = null,
        ?array $asset = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            /* name: $name, */
            productId: $productId,
            categoryId: $categoryId,
            locationId: $locationId,
            asset: $asset,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}