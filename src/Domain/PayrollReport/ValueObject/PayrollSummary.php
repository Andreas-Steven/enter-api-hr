<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\ValueObject;

/**
 * Aggregate of every calculated payroll summary field for one employee over
 * one period (the `payroll_summary` JSONB column).
 *
 * Folds the source AttendanceSummary's calculated summary together with the
 * AttendanceFineRate deduction amounts into the per-field Value Objects:
 *
 *   - {@see ScheduleCount}       (HEK days + obligation days)
 *   - {@see AttendanceCount}     (present / absent / sick / leave days)
 *   - {@see ComplianceCount}     (fulfilled / unfulfilled / deficit / excess / pending review)
 *   - {@see TimeStatistic}       (late count, early-out count, incomplete count)
 *   - {@see OvertimeStatistic}   (overtime days + minutes)
 *   - {@see LeaveStatistic}      (leave quota used)
 *   - {@see DeductionBreakdown}  (total amount + per-incident breakdown)
 */
final readonly class PayrollSummary
{
    private function __construct(
        private ScheduleCount $schedule,
        private AttendanceCount $attendance,
        private ComplianceCount $compliance,
        private TimeStatistic $time,
        private OvertimeStatistic $overtime,
        private LeaveStatistic $leave,
        private DeductionBreakdown $deduction,
    ) {}

    public static function empty(): self
    {
        return new self(
            ScheduleCount::empty(),
            AttendanceCount::empty(),
            ComplianceCount::empty(),
            TimeStatistic::empty(),
            OvertimeStatistic::empty(),
            LeaveStatistic::empty(),
            DeductionBreakdown::empty(),
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            ScheduleCount::fromArray($data['schedule'] ?? []),
            AttendanceCount::fromArray($data['attendance'] ?? []),
            ComplianceCount::fromArray($data['compliance'] ?? []),
            TimeStatistic::fromArray($data['time'] ?? []),
            OvertimeStatistic::fromArray($data['overtime'] ?? []),
            LeaveStatistic::fromArray($data['leave'] ?? []),
            DeductionBreakdown::fromArray($data['deduction'] ?? []),
        );
    }

    public static function fromValues(
        ScheduleCount $schedule,
        AttendanceCount $attendance,
        ComplianceCount $compliance,
        TimeStatistic $time,
        OvertimeStatistic $overtime,
        LeaveStatistic $leave,
        DeductionBreakdown $deduction,
    ): self {
        return new self(
            $schedule,
            $attendance,
            $compliance,
            $time,
            $overtime,
            $leave,
            $deduction,
        );
    }

    public function getSchedule(): ScheduleCount
    {
        return $this->schedule;
    }

    public function getAttendance(): AttendanceCount
    {
        return $this->attendance;
    }

    public function getCompliance(): ComplianceCount
    {
        return $this->compliance;
    }

    public function getTime(): TimeStatistic
    {
        return $this->time;
    }

    public function getOvertime(): OvertimeStatistic
    {
        return $this->overtime;
    }

    public function getLeave(): LeaveStatistic
    {
        return $this->leave;
    }

    public function getDeduction(): DeductionBreakdown
    {
        return $this->deduction;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'schedule' => $this->schedule->toArray(),
            'attendance' => $this->attendance->toArray(),
            'compliance' => $this->compliance->toArray(),
            'time' => $this->time->toArray(),
            'overtime' => $this->overtime->toArray(),
            'leave' => $this->leave->toArray(),
            'deduction' => $this->deduction->toArray(),
        ];
    }
}
