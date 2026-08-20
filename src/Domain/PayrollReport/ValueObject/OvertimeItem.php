<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

/**
 * A single per-day overtime entry inside {@see OvertimeSummary}.
 */
final readonly class OvertimeItem
{
    private function __construct(
        private string $date,
        private int $minutes,
        private int $amount,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            (string) ($data['date'] ?? ''),
            (int) ($data['minutes'] ?? 0),
            (int) ($data['amount'] ?? 0),
        );
    }

    public static function fromValues(string $date, int $minutes, int $amount): self
    {
        return new self($date, $minutes, $amount);
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getMinutes(): int
    {
        return $this->minutes;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'minutes' => $this->minutes,
            'amount' => $this->amount,
        ];
    }
}
