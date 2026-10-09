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

    public function withoutDeletedItems(): self
    {
        return new self(array_values(array_filter(
            $this->items,
            static fn (AssetItem $item): bool => empty($item->changeLog['deleted_at']),
        )));
    }

    public function toArray(): array
    {
        $result = [];
        $totalPrice = 0;

        foreach ($this->items as $item) {
            $result[$item->code] = $item->toArray();
            $totalPrice += $item->price;
        }

        return [
            'items' => $result,
            'quantity' => count($result),
            'total_price' => $totalPrice == (int) $totalPrice ? (int) $totalPrice : $totalPrice,
        ];
    }
}