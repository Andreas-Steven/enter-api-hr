<?php

declare(strict_types=1);

namespace App\Application\GeneralAffair\Dto;

final readonly class GeneralAffairDetailInfo
{
    public function __construct(
        public ?array $product,
        public ?array $category,
        public ?array $location,
    ) {}

    public function toArray(): array
    {
        return [
            'product' => $this->product,
            'category' => $this->category,
            'location' => $this->location,
        ];
    }
}