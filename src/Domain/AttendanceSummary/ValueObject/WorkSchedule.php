<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

// Domain Layer
use App\Domain\Employee\Entity\Employee;

/**
 * Snapshot of the work schedule used to evaluate a summary.
 *
 * Stored as JSON in the `work_schedule` column as a flat list of per-day-of-week
 * entries (shift + work_pattern_rule). Built from the employee's branch
 * work-pattern so the rules that produced the summary remain auditable.
 */
final readonly class WorkSchedule
{
    private function __construct(
        private array $data,
    ) {}

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromArray(array $data): self
    {
        // Backward compatibility: legacy records stored the weekly schedule
        // nested under a `weekly_schedule` key. New records store the flat
        // per-day-of-week list directly.
        if (isset($data['weekly_schedule'])) {
            return new self(array_values($data['weekly_schedule']));
        }

        return new self(array_values($data));
    }

    public static function fromEmployee(Employee $employee): self
    {
        $pattern = $employee->toWorkScheduleSnapshot();

        if (empty($pattern)) {
            return self::empty();
        }

        $weeklySchedule = $pattern['working_days'] ?? [];

        return new self(array_values($weeklySchedule));
    }

    /**
     * Looks up the scheduled shift for a given date (Y-m-d), based on the
     * captured weekly schedule. Returns null when the day is a non-working day.
     *
     * @return array<string, mixed>|null
     */
    public function findShiftForDate(string $date): ?array
    {
        $day = $this->findDayForDate($date);
        return $day['shift'] ?? null;
    }

    /**
     * Looks up the full day entry (shift + day_of_week + work_pattern_rule)
     * for a given date (Y-m-d). Returns null when the day is a non-working day.
     *
     * @return array<string, mixed>|null
     */
    public function findDayForDate(string $date): ?array
    {
        $dayOfWeek = (int) date('N', strtotime($date));

        foreach ($this->data as $day) {
            if ((int) ($day['day_of_week'] ?? 0) === $dayOfWeek) {
                return $day;
            }
        }

        return null;
    }

    /** @return list<array<string, mixed>> */
    public function toArray(): array
    {
        return $this->data;
    }
}
