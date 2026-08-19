<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

// Domain Layer
use App\Domain\Shared\Enums\IncidentType;

/**
 * Deduction breakdown (the `payroll_summary.deduction` block).
 *
 * Aggregates the total deduction amount and a per-incident-type breakdown.
 * Each incident type entry has a count (or days for ABSENT) and the
 * corresponding deduction amount, calculated from the AttendanceFineRate
 * configuration multiplied by the occurrence count.
 *
 * Breakdown shape:
 *   LATE:        { count, amount }
 *   ABSENT:      { days, amount }
 *   EARLY_OUT:   { count, amount }
 *   MISSING_IN:  { count, amount }
 *   MISSING_OUT: { count, amount }
 */
final readonly class DeductionBreakdown
{
    /** @return array<string, array<string, int>> */
    private static function defaultBreakdown(): array
    {
        return [
            IncidentType::LATE->value => ['count' => 0, 'amount' => 0],
            IncidentType::ABSENT->value => ['days' => 0, 'amount' => 0],
            IncidentType::EARLY_OUT->value => ['count' => 0, 'amount' => 0],
            IncidentType::MISSING_IN->value => ['count' => 0, 'amount' => 0],
            IncidentType::MISSING_OUT->value => ['count' => 0, 'amount' => 0],
        ];
    }

    /**
     * @param int $totalAmount
     * @param array<string, array<string, int>> $breakdown
     */
    private function __construct(
        private int $totalAmount,
        private array $breakdown,
    ) {}

    public static function empty(): self
    {
        return new self(0, self::defaultBreakdown());
    }

    public static function fromArray(array $data): self
    {
        $breakdown = array_replace_recursive(
            self::defaultBreakdown(),
            $data['breakdown'] ?? [],
        );

        return new self(
            (int) ($data['total_amount'] ?? 0),
            $breakdown,
        );
    }

    /**
     * @param array<string, array<string, int>> $breakdown
     */
    public static function fromValues(int $totalAmount, array $breakdown): self
    {
        return new self(
            $totalAmount,
            array_replace_recursive(self::defaultBreakdown(), $breakdown),
        );
    }

    public function getTotalAmount(): int
    {
        return $this->totalAmount;
    }

    /**
     * Returns the breakdown entry for a given incident type.
     *
     * @return array<string, int>
     */
    public function getEntry(IncidentType $type): array
    {
        return $this->breakdown[$type->value] ?? ['count' => 0, 'amount' => 0];
    }

    /** @return array<string, array<string, int>> */
    public function getBreakdown(): array
    {
        return $this->breakdown;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'total_amount' => $this->totalAmount,
            'breakdown' => $this->breakdown,
        ];
    }
}
