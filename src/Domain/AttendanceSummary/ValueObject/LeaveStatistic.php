<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

/**
 * Leave statistics (the `summary.leave` block).
 *
 * Tracks used leave quota and the remaining quota snapshot taken from the
 * EmployeeLeave aggregate at generation time. `remaining_amount` is nullable
 * because not every period has a matching EmployeeLeave quota record.
 */
final readonly class LeaveStatistic
{
    private function __construct(
        private int $quotaUsed,
        private ?int $remainingAmount,
    ) {}

    public static function empty(): self
    {
        return new self(0, null);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['quota_used'] ?? 0),
            isset($data['remaining_amount']) ? (int) $data['remaining_amount'] : null,
        );
    }

    public static function fromValues(int $quotaUsed, ?int $remainingAmount = null): self
    {
        return new self($quotaUsed, $remainingAmount);
    }

    public function getQuotaUsed(): int
    {
        return $this->quotaUsed;
    }

    public function getRemainingAmount(): ?int
    {
        return $this->remainingAmount;
    }

    /** @return array<string, int|null> */
    public function toArray(): array
    {
        return [
            'quota_used' => $this->quotaUsed,
            'remaining_amount' => $this->remainingAmount,
        ];
    }
}
