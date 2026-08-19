<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

/**
 * Leave statistics (the `payroll_summary.leave` block).
 *
 * Tracks used leave quota for the period.
 */
final readonly class LeaveStatistic
{
    private function __construct(
        private int $quotaUsed,
    ) {}

    public static function empty(): self
    {
        return new self(0);
    }

    public static function fromArray(array $data): self
    {
        return new self((int) ($data['quota_used'] ?? 0));
    }

    public static function fromValues(int $quotaUsed): self
    {
        return new self($quotaUsed);
    }

    public function getQuotaUsed(): int
    {
        return $this->quotaUsed;
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return ['quota_used' => $this->quotaUsed];
    }
}
