<?php

declare(strict_types=1);

namespace App\Domain\GeneralAffair\Entity;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

// Domain Layer
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;

use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\ValueObject\SyncMdb;
use App\Domain\GeneralAffair\ValueObject\Asset;

final class GeneralAffair
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'GeneralAffair';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        /* private string $name, */
        private int $productId,
        private int $categoryId,
        private int $locationId,
        private Asset $asset,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ) {
        $this->resource = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public static function getResource(): string
    {
        return self::RESOURCE;
    }

    public static function create(
        /* string $name, */
        int $productId,
        int $categoryId,
        int $locationId,
        Asset $asset,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null,
            /* name: $name, */
            productId: $productId,
            categoryId: $categoryId,
            locationId: $locationId,
            asset: $asset,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: LockVersion::create()
        );
    }

    public static function reconstitute(
        int $id,
        /* string $name, */
        int $productId,
        int $categoryId,
        int $locationId,
        Asset $asset,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
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
            syncMdb: $syncMdb,
            lockVersion: $lockVersion ?? LockVersion::create()
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            /* 'name' => $this->name, */
            'product_id' => $this->productId,
            'category_id' => $this->categoryId,
            'location_id' => $this->locationId,
            'asset' => $this->asset->toArray(),
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            SyncMdb::field() => $this->syncMdb?->value(),
            LockVersion::field() => $this->lockVersion->value(),
        ];
    }
    
    public function restore(): void
    {
        if (!$this->status->isDeleted()) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'resource.not_deleted',
                    params: ['id' => $this->id]
                )
            );
        }
        
        $this->status = ResourceStatus::restored();
    }

    /**
     * Place GeneralAffair-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */ 
    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function getLocationId(): int
    {
        return $this->locationId;
    }

    public function getAsset(): Asset
    {
        return $this->asset;
    }

    public function updateAsset(Asset $asset): void
    {
        $this->asset = $asset;
    }

    public function updateProductId(int $productId): void
    {
        $this->productId = $productId;
    }

    public function updateCategoryId(int $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function updateLocationId(int $locationId): void
    {
        $this->locationId = $locationId;
    }
}
