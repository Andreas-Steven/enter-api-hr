<?php

declare(strict_types=1);

namespace App\Domain\GeneralAffair\ValueObject;

final readonly class Asset
{
    /** @param AssetItem[] $items */
    private function __construct(
        private array $items
    ) {}

    /** @param AssetItem[] $items */
    public static function fromItems(array $items): self
    {
        return new self($items);
    }

    public static function fromJson(mixed $json): self
    {
        if (empty($json)) {
            return new self([]);
        }

        $data = is_array($json) ? $json : json_decode((string) $json, true);
        $storedItems = is_array($data) ? ($data['items'] ?? []) : [];
        $items = [];

        foreach ($storedItems as $itemData) {
            $items[] = AssetItem::fromStoredArray(data: $itemData);
        }

        return new self($items);
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[$item->code] = $item->toArray();
        }

        return ['items' => $result];
    }
}