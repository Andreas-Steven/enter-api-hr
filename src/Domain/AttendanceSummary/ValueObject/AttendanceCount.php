<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

/**
 * Attendance day counters (the `summary.attendance` block).
 *
 * Pure attendance-derived counts: present, absent, sick, leave days.
 * The schedule-based obligation/HEK counts live in {@see ScheduleCount}.
 */
final readonly class AttendanceCount
{
    private function __construct(
        private int $presentDays,
        private int $absentDays,
        private int $sickDays,
        private int $leaveDays,
    ) {}

    public static function empty(): self
    {
        return new self(0, 0, 0, 0);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            (int) ($data['present_days'] ?? 0),
            (int) ($data['absent_days'] ?? 0),
            (int) ($data['sick_days'] ?? 0),
            (int) ($data['leave_days'] ?? 0),
        );
    }

    public static function fromValues(
        int $presentDays,
        int $absentDays,
        int $sickDays = 0,
        int $leaveDays = 0,
    ): self {
        return new self(
            $presentDays,
            $absentDays,
            $sickDays,
            $leaveDays,
        );
    }

    public function getPresentDays(): int
    {
        return $this->presentDays;
    }

    public function getAbsentDays(): int
    {
        return $this->absentDays;
    }

    public function getSickDays(): int
    {
        return $this->sickDays;
    }

    public function getLeaveDays(): int
    {
        return $this->leaveDays;
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'present_days' => $this->presentDays,
            'absent_days' => $this->absentDays,
            'sick_days' => $this->sickDays,
            'leave_days' => $this->leaveDays,
        ];
    }
}
