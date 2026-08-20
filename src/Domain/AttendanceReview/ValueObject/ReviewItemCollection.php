<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReview\ValueObject;

// Domain Layer
use App\Domain\Shared\Enums\ReviewItemStatus;

/**
 * Collection of {@see ReviewItem} backing an AttendanceReview.
 *
 * Stored as JSON in the `items` column as a flat list. Provides convenience
 * accessors for counting items by status, which feeds the denormalized
 * {@see ReviewCounts} block.
 */
final readonly class ReviewItemCollection
{
    /** @var list<ReviewItem> */
    private array $items;

    /**
     * @param list<ReviewItem> $items
     */
    private function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromArray(array $data): self
    {
        $items = [];

        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $items[] = ReviewItem::fromArray($item);
        }

        return new self($items);
    }

    /**
     * @param list<ReviewItem> $items
     */
    public static function fromItems(array $items): self
    {
        return new self($items);
    }

    /** @return list<ReviewItem> */
    public function getItems(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    public function countByStatus(ReviewItemStatus $status): int
    {
        $count = 0;

        foreach ($this->items as $item) {
            if ($item->getStatus() === $status) {
                $count++;
            }
        }

        return $count;
    }

    /** @return list<array<string, mixed>> */
    public function toArray(): array
    {
        return array_map(
            static fn(ReviewItem $item): array => $item->toArray(),
            $this->items,
        );
    }
}
