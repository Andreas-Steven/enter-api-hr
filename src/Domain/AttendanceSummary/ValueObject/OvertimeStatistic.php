<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

/**
 * Overtime statistics (the `summary.overtime` block).
 *
 * Overtime is derived from attendance by comparing actual work duration
 * against the scheduled shift duration: any surplus minutes on an obligation
 * day count as overtime.
 *
 * `items` captures the per-day overtime breakdown (date + minutes) so
 * downstream modules (e.g. PayrollReport) can calculate per-day amounts.
 */
final readonly class OvertimeStatistic
{
    /**
     * @param list<array{date: string, minutes: int}> $items
     */
    private function __construct(
        private int $days,
        private int $minutes,
        private array $items = [],
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, []);
    }

    public static function fromArray(array $data): self
    {
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $items[] = [
                    'date' => (string) ($item['date'] ?? ''),
                    'minutes' => (int) ($item['minutes'] ?? 0),
                ];
            }
        }

        return new self(
            (int) ($data['days'] ?? 0),
            (int) ($data['minutes'] ?? 0),
            $items,
        );
    }

    /**
     * @param list<array{date: string, minutes: int}> $items
     */
    public static function fromValues(int $days, int $minutes, array $items = []): self
    {
        return new self($days, $minutes, $items);
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function getMinutes(): int
    {
        return $this->minutes;
    }

    /**
     * @return list<array{date: string, minutes: int}>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'days' => $this->days,
            'minutes' => $this->minutes,
            'items' => $this->items,
        ];
    }
}
