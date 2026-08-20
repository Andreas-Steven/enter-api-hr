<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

/**
 * Deduction breakdown (the `payroll_summary.deduction` block).
 *
 * References PayrollDeduction records by ID. Each item corresponds to one
 * PayrollDeduction row persisted in the payroll_deduction table.
 *
 * Shape:
 *   {
 *     total_amount: int,
 *     items: [
 *       {
 *         id: int,
 *         name: string,
 *         deduction_type_id: int,
 *         amount: int,
 *         note: ?string
 *       }
 *     ]
 *   }
 */
final readonly class DeductionBreakdown
{
    /**
     * @param int $totalAmount
     * @param list<array<string, mixed>> $items
     */
    private function __construct(
        private int $totalAmount,
        private array $items,
    ) {}

    public static function empty(): self
    {
        return new self(0, []);
    }

    public static function fromArray(array $data): self
    {
        $items = $data['items'] ?? [];

        $totalAmount = (int) ($data['total_amount'] ?? 0);

        return new self($totalAmount, $items);
    }

    /**
     * @param list<array<string, mixed>> $items
     */
    public static function fromValues(int $totalAmount, array $items): self
    {
        return new self($totalAmount, $items);
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    /** @return list<array<string, mixed>> */
    public function getItems(): array
    {
        return $this->items;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'total_amount' => $this->totalAmount,
            'items' => $this->items,
        ];
    }
}
