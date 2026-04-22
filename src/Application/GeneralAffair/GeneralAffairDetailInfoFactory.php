<?php

declare(strict_types=1);

namespace App\Application\GeneralAffair;

use App\Application\GeneralAffair\Dto\GeneralAffairDetailInfo;
use App\Domain\GeneralAffairCategory\Repository\GeneralAffairCategoryRepositoryInterface;
use App\Domain\GeneralAffairLocation\Repository\GeneralAffairLocationRepositoryInterface;
use App\Domain\GeneralAffairProduct\Repository\GeneralAffairProductRepositoryInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

final readonly class GeneralAffairDetailInfoFactory
{
    public function __construct(
        private GeneralAffairProductRepositoryInterface $productRepository,
        private GeneralAffairCategoryRepositoryInterface $categoryRepository,
        private GeneralAffairLocationRepositoryInterface $locationRepository,
    ) {}

    /**
     * Membangun array detail_info untuk response data
     */
    public function buildFromPrimitives(
        ?int $productId,
        ?int $categoryId,
        ?int $locationId,
    ): GeneralAffairDetailInfo 
    {
        $product = $this->buildProductArray(productId: $productId);
        $category = $this->buildCategoryArray(categoryId: $categoryId);
        $location = $this->buildLocationArray(locationId: $locationId);

        return new GeneralAffairDetailInfo(
            product: $product,
            category: $category,
            location: $location,
        );
    }

    private function buildProductArray(?int $productId): ?array
    {
        if ($productId === null) {
            return null;
        }

        $product = $this->productRepository->findById(
            id: $productId,
            status: ResourceStatus::active()->value()
        );

        if ($product === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'GeneralAffairProduct',
                        'field' => 'id',
                        'value' => $productId,
                    ]
                )
            );
        }

        return [
            'id' => $product->getId(),
            'name' => $product->getName(),
        ];
    }

    private function buildCategoryArray(?int $categoryId): ?array
    {
        if ($categoryId === null) {
            return null;
        }

        $category = $this->categoryRepository->findById(
            id: $categoryId,
            status: ResourceStatus::active()->value()
        );

        if ($category === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'GeneralAffairCategory',
                        'field' => 'id',
                        'value' => $categoryId,
                    ]
                )
            );
        }

        return [
            'id' => $category->getId(),
            'name' => $category->getName(),
        ];
    }

    private function buildLocationArray(?int $locationId): ?array
    {
        if ($locationId === null) {
            return null;
        }

        $location = $this->locationRepository->findById(
            id: $locationId,
            status: ResourceStatus::active()->value()
        );

        if ($location === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'GeneralAffairLocation',
                        'field' => 'id',
                        'value' => $locationId,
                    ]
                )
            );
        }

        return [
            'id' => $location->getId(),
            'name' => $location->getName(),
        ];
    }
}