<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

// Domain Layer
use App\Domain\Attendance\ValueObject\AttendanceItem;
use App\Domain\Shared\Enums\MissingPolicy;

/**
 * Snapshot of the attendance policy used to evaluate a summary.
 *
 * Stored as JSON in the `policy` column. Captured from the policy attached to
 * the employee's attendance records so the thresholds (grace periods, minimum
 * work minutes, missing-in/out handling) that produced the summary remain
 * auditable even if the policy is later changed.
 */
final readonly class Policy
{
    /** @return array<string, mixed> */
    private static function defaultShape(): array
    {
        return [
            'id' => null,
            'name' => null,
            'late_grace_in' => null,
            'early_grace_out' => null,
            'min_work_full_day' => null,
            'min_work_half_day' => null,
            'min_overtime_minutes' => null,
            'missing_in_policy' => null,
            'missing_out_policy' => null,
        ];
    }

    private function __construct(
        private array $data,
    ) {}

    public static function empty(): self
    {
        return new self(self::defaultShape());
    }

    public static function fromArray(array $data): self
    {
        return new self(array_replace_recursive(self::defaultShape(), $data));
    }

    /**
     * Picks the first attendance policy found across the daily items.
     *
     * @param array<string, AttendanceItem> $dailyItems
     */
    public static function fromDailyItems(array $dailyItems): self
    {
        foreach ($dailyItems as $item) {
            if ($item->attendancePolicies !== null) {
                return self::fromAttendancePolicies($item->attendancePolicies);
            }
        }

        return self::empty();
    }

    /** @param array<string, mixed> $attendancePolicies */
    public static function fromAttendancePolicies(array $attendancePolicies): self
    {
        $detail = $attendancePolicies['policy_detail'] ?? [];

        return new self([
            'id' => $attendancePolicies['id'] ?? null,
            'name' => $attendancePolicies['name'] ?? null,
            'late_grace_in' => $detail['late_grace_in'] ?? null,
            'early_grace_out' => $detail['early_grace_out'] ?? null,
            'min_work_full_day' => $detail['min_work_full_day'] ?? null,
            'min_work_half_day' => $detail['min_work_half_day'] ?? null,
            'min_overtime_minutes' => $detail['min_overtime_minutes'] ?? null,
            'missing_in_policy' => $detail['missing_in_policy'] ?? null,
            'missing_out_policy' => $detail['missing_out_policy'] ?? null,
        ]);
    }

    public function getMinWorkFullDay(): ?int
    {
        return $this->data['min_work_full_day'] !== null
            ? (int) $this->data['min_work_full_day']
            : null;
    }

    public function getMinWorkHalfDay(): ?int
    {
        return $this->data['min_work_half_day'] !== null
            ? (int) $this->data['min_work_half_day']
            : null;
    }

    public function getLateGraceIn(): int
    {
        return (int) ($this->data['late_grace_in'] ?? 0);
    }

    public function getEarlyGraceOut(): int
    {
        return (int) ($this->data['early_grace_out'] ?? 0);
    }

    public function getMinOvertimeMinutes(): int
    {
        return (int) ($this->data['min_overtime_minutes'] ?? 0);
    }

    public function getMissingInPolicy(): ?MissingPolicy
    {
        return self::resolveMissingPolicy($this->data['missing_in_policy'] ?? null);
    }

    public function getMissingOutPolicy(): ?MissingPolicy
    {
        return self::resolveMissingPolicy($this->data['missing_out_policy'] ?? null);
    }

    private static function resolveMissingPolicy(mixed $value): ?MissingPolicy
    {
        if ($value instanceof MissingPolicy) {
            return $value;
        }

        if ($value === null || $value === '') {
            return null;
        }

        return MissingPolicy::tryFrom((string) $value);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->data;
    }
}
