<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

/**
 * Overtime summary (the top-level `overtime_summary` block).
 *
 * Contains a per-day breakdown of overtime (items) plus the aggregate total
 * amount. Each item records the date, overtime minutes, and the calculated
 * amount (minutes × overtime_rate_per_min from PayrollRate enum).
 *
 * Unlike deduction, overtime is an earning (penambah gaji), not a deduction.
 */
final readonly class OvertimeSummary
{
    /**
     * @param list<OvertimeItem> $items
     */
    private function __construct(
        private array $items,
        private int $totalAmount,
    ) {}

    public static function empty(): self
    {
        return new self([], 0);
    }

    public static function fromArray(array $data): self
    {
        $items = [];
        foreach ($data['items'] ?? [] as $item) {
            $items[] = OvertimeItem::fromArray($item);
        }

        return new self(
            $items,
            (int) ($data['total_amount'] ?? 0),
        );
    }

    /**
     * @param list<OvertimeItem> $items
     */
    public static function fromValues(array $items, int $totalAmount): self
    {
        return new self($items, $totalAmount);
    }

    /**
     * @return list<OvertimeItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'items' => array_map(fn(OvertimeItem $item) => $item->toArray(), $this->items),
            'total_amount' => $this->totalAmount,
        ];
    }
}
