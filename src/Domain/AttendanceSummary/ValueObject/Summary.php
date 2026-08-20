<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

// Domain Layer
use App\Domain\Attendance\ValueObject\AttendanceItem;
use App\Domain\Shared\Enums\PermissionType;

/**
 * Aggregate of every calculated summary field for one employee over one period.
 *
 * This is the only summary block that is *calculated* during generation; it
 * folds the employee's daily attendance items together with the work-schedule
 * rules (captured in {@see WorkSchedule}) into the per-field Value Objects:
 *
 *   - {@see ScheduleCount}    (HEK days + obligation days — from schedule, not attendance)
 *   - {@see AttendanceCount}  (present / absent / sick / leave / permission days)
 *   - {@see ComplianceCount}  (fulfilled / unfulfilled / deficit / excess / pending review)
 *   - {@see TimeStatistic}    (late count+minutes, early-out count+minutes, incomplete)
 *   - {@see OvertimeStatistic}(overtime days + minutes)
 *   - {@see DeductionStatistic} (deduction days)
 *   - {@see LeaveStatistic}   (leave quota used)
 *
 * Sick / leave / permission days and leave quota are sourced from the
 * AttendancePermission and EmployeeLeave aggregates. When those are not wired
 * into the generation flow they resolve to zero.
 */
final readonly class Summary
{
    private function __construct(
        private ScheduleCount $schedule,
        private AttendanceCount $attendance,
        private ComplianceCount $compliance,
        private TimeStatistic $time,
        private OvertimeStatistic $overtime,
        private DeductionStatistic $deduction,
        private LeaveStatistic $leave,
        private array $rules,
    ) {}

    public static function empty(): self
    {
        return new self(
            ScheduleCount::empty(),
            AttendanceCount::empty(),
            ComplianceCount::empty(),
            TimeStatistic::empty(),
            OvertimeStatistic::empty(),
            DeductionStatistic::empty(),
            LeaveStatistic::empty(),
            [],
        );
    }

    public static function fromArray(array $data): self
    {
        // Backward compatibility: old records stored obligation_days inside
        // the attendance block. If schedule is missing, derive it from there.
        $scheduleData = $data['schedule'] ?? [];
        if (empty($scheduleData) && isset($data['attendance']['obligation_days'])) {
            $oldObligation = (int) $data['attendance']['obligation_days'];
            $scheduleData = [
                'hek_days' => $oldObligation,
                'obligation_days' => 0,
            ];
        }

        return new self(
            ScheduleCount::fromArray($scheduleData),
            AttendanceCount::fromArray($data['attendance'] ?? []),
            ComplianceCount::fromArray($data['compliance'] ?? []),
            TimeStatistic::fromArray($data['time'] ?? []),
            OvertimeStatistic::fromArray($data['overtime'] ?? []),
            DeductionStatistic::fromArray($data['deduction'] ?? []),
            LeaveStatistic::fromArray($data['leave'] ?? []),
            $data['rules'] ?? [],
        );
    }

    /**
     * Computes the full summary for one employee from their daily attendance
     * items, the captured work schedule, approved permissions, and holidays.
     *
     * Schedule counts are derived from the work schedule:
     *  - REQUIRED_WEEKDAY (HEK): every matching day-of-week that is not a
     *    holiday (when the rule's holiday_wins = 1).
     *  - PERIODIC_REQUIRED_DAY (Obligation): up to target_count matching
     *    non-holiday days.
     *
     * @param WorkSchedule $workSchedule  Captured schedule (per-day shift + rule).
     * @param array<string, AttendanceItem> $dailyItems  Map of date (Y-m-d) => attendance item.
     * @param list<string> $periodDays  Every calendar day in the period range.
     * @param array<string, int> $permissionDateMap  Map of date (Y-m-d) => PermissionType value for approved permissions.
     * @param array<string, int> $holidayDates  Set of holiday dates (Y-m-d => day_off value).
     * @param Policy|null $policy  Attendance policy (grace periods, min work).
     */
    public static function compute(
        WorkSchedule $workSchedule,
        array $dailyItems,
        array $periodDays,
        array $permissionDateMap = [],
        array $holidayDates = [],
        ?Policy $policy = null,
    ): self {
        // 1. Determine schedule counts (HEK + Obligation) per rule.
        $hekDays = 0;
        $obligationDays = 0;
        $saturdayDays = 0;
        $sundayDays = 0;
        $obligationDateSet = [];

        // First pass: count HEK (REQUIRED_WEEKDAY) and collect PERIODIC_REQUIRED_DAY
        // candidate dates per rule_id.
        $periodicCandidates = []; // ruleId => ['dates' => [...], 'target_count' => int]

        foreach ($periodDays as $date) {
            $dayOfWeek = (int) date('N', strtotime($date));
            if ($dayOfWeek === 6) {
                $saturdayDays++;
            } elseif ($dayOfWeek === 7) {
                $sundayDays++;
            }

            $day = $workSchedule->findDayForDate($date);
            if ($day === null) {
                continue;
            }

            $shift = $day['shift'] ?? null;
            if ($shift === null) {
                continue;
            }

            $rule = $day['work_pattern_rule'] ?? null;
            $ruleType = $rule['rule_type'] ?? null;
            $holidayWins = (int) ($rule['holiday_wins'] ?? 0);

            // Skip holidays when holiday_wins is set.
            $isHoliday = isset($holidayDates[$date]);
            if ($isHoliday && $holidayWins === 1) {
                continue;
            }

            if ($ruleType === 'PERIODIC_REQUIRED_DAY') {
                $ruleId = (int) ($rule['id'] ?? 0);
                if (!isset($periodicCandidates[$ruleId])) {
                    $periodicCandidates[$ruleId] = [
                        'dates' => [],
                        'target_count' => (int) ($rule['target_count'] ?? 0),
                    ];
                }
                $periodicCandidates[$ruleId]['dates'][] = $date;
            } else {
                $hekDays++;
                $obligationDateSet[$date] = true;
            }
        }

        // Second pass: for each PERIODIC_REQUIRED_DAY rule, mark the first
        // target_count ATTENDED candidate dates as obligation. Unattended
        // candidates beyond attended count are NOT obligation (no absent penalty).
        foreach ($periodicCandidates as $ruleId => $data) {
            $targetCount = $data['target_count'];
            $marked = 0;

            // Attended dates first.
            foreach ($data['dates'] as $date) {
                if ($marked >= $targetCount) {
                    break;
                }
                if (!isset($dailyItems[$date])) {
                    continue;
                }
                $obligationDateSet[$date] = true;
                $marked++;
                $obligationDays++;
            }
        }

        $totalObligation = $hekDays + $obligationDays;

        // Grace periods from policy (applied to deficit/excess threshold).
        $earlyGraceOut = $policy?->getEarlyGraceOut() ?? 0;

        // 2. Fold daily attendance into raw signals.
        $presentDays = 0;
        $fulfilled = 0;
        $lateCount = 0;
        $lateMinutes = 0;
        $earlyOutCount = 0;
        $earlyOutMinutes = 0;
        $incomplete = 0;
        $deficit = 0;
        $excess = 0;
        $overtimeDays = 0;
        $overtimeMinutes = 0;
        $overtimeItems = [];

        foreach ($dailyItems as $date => $item) {
            $detail = $item->info['detail'] ?? [];
            $isPresent = $item->startTime !== null;
            $workDuration = (int) ($item->totalWorkMinutes ?? 0);

            // Required minutes: only for obligation days.
            $required = isset($obligationDateSet[$date])
                ? self::shiftMinutes($workSchedule->findShiftForDate($date))
                : 0;

            if ($isPresent) {
                $presentDays++;
            }

            // Time statistics.
            if (!empty($detail['late'])) {
                $lateCount++;
                $lateMinutes += (int) ($detail['late_minutes'] ?? 0);
            }

            $earlyOut = (int) ($detail['early_out'] ?? 0);
            if ($earlyOut > 0) {
                $earlyOutCount++;
                $earlyOutMinutes += $earlyOut;
            }

            $isMissingIn = !empty($detail['missing_in']);
            $isMissingOut = !empty($detail['missing_out']);
            if ($isMissingIn || $isMissingOut) {
                $incomplete++;
            }

            // Compliance & overtime (require a worked day).
            $isFullDay = $isPresent && !empty($detail['full_day']);
            if ($isFullDay) {
                $fulfilled++;
            }

            if ($required > 0 && $workDuration > 0) {
                $diff = $workDuration - $required;
                if ($diff < -$earlyGraceOut && !$isFullDay) {
                    // Worked but fell short of required duration beyond grace
                    // AND did not meet full_day threshold => deficit.
                    $deficit++;
                } elseif ($diff > 0) {
                    // Worked beyond the required duration (overtime on working day).
                    $excess++;
                    $overtimeDays++;
                    $overtimeMinutes += $diff;
                    $overtimeItems[] = ['date' => $date, 'minutes' => $diff];
                }
                // Within grace OR full_day: fulfilled, no deficit/excess.
            } elseif ($required === 0 && $workDuration > 0) {
                // Worked on a non-obligation day => counts as overtime.
                $overtimeDays++;
                $overtimeMinutes += $workDuration;
                $overtimeItems[] = ['date' => $date, 'minutes' => $workDuration];
            }
        }

        // 3. Categorize absent obligation days that have approved permissions.
        $sickDays = 0;
        $leaveDays = 0;
        $leaveQuotaUsed = 0;

        foreach ($periodDays as $date) {
            // Only obligation days where the employee is absent can be excused.
            if (!isset($obligationDateSet[$date])) {
                continue;
            }
            if (isset($dailyItems[$date])) {
                continue;
            }

            $permissionTypeValue = $permissionDateMap[$date] ?? null;
            if ($permissionTypeValue === null) {
                continue;
            }

            $permissionType = PermissionType::tryFrom($permissionTypeValue);
            if ($permissionType === null) {
                continue;
            }

            switch ($permissionType) {
                case PermissionType::SICK:
                case PermissionType::SICK_WITH_RECEIPT:
                    $sickDays++;
                    break;
                case PermissionType::ANNUAL_LEAVE:
                case PermissionType::PERMISSION_LEAVE:
                    $leaveDays++;
                    $leaveQuotaUsed++;
                    break;
                case PermissionType::UNPAID_LEAVE:
                    $leaveDays++;
                    break;
                case PermissionType::LEAVE_EARLY:
                case PermissionType::LATE:
                    // These are partial-day permissions; the employee still
                    // attended, so they're already counted in present_days.
                    break;
            }
        }

        // 4. Derive the dependent counters.
        $absentDays = max(0, $totalObligation - $presentDays - $sickDays - $leaveDays);
        $unfulfilled = max(0, $totalObligation - $fulfilled - $sickDays - $leaveDays);
        $pendingReview = $incomplete;
        $deductionDays = $absentDays + $incomplete;

        return new self(
            ScheduleCount::fromValues(
                hekDays: $hekDays,
                obligationDays: $obligationDays,
                saturdayDays: $saturdayDays,
                sundayDays: $sundayDays,
            ),
            AttendanceCount::fromValues(
                presentDays: $presentDays,
                absentDays: $absentDays,
                sickDays: $sickDays,
                leaveDays: $leaveDays,
            ),
            ComplianceCount::fromValues(
                fulfilled: $fulfilled,
                unfulfilled: $unfulfilled,
                deficit: $deficit,
                excess: $excess,
                pendingReview: $pendingReview,
            ),
            TimeStatistic::fromValues(
                lateCount: $lateCount,
                lateMinutes: $lateMinutes,
                earlyOutCount: $earlyOutCount,
                earlyOutMinutes: $earlyOutMinutes,
                incomplete: $incomplete,
            ),
            OvertimeStatistic::fromValues(
                days: $overtimeDays,
                minutes: $overtimeMinutes,
                items: $overtimeItems,
            ),
            DeductionStatistic::fromValues(days: $deductionDays),
            LeaveStatistic::fromValues(quotaUsed: $leaveQuotaUsed),
            [],
        );
    }

    /**
     * Returns the scheduled shift duration in minutes for a shift snapshot.
     * Handles overnight shifts (end time on the following day).
     *
     * @param array<string, mixed>|null $shift
     */
    private static function shiftMinutes(?array $shift): int
    {
        if ($shift === null) {
            return 0;
        }

        $start = $shift['start_time'] ?? null;
        $end = $shift['end_time'] ?? null;

        if (!$start || !$end) {
            return 0;
        }

        try {
            $startDt = new \DateTimeImmutable($start);
            $endDt = new \DateTimeImmutable($end);
        } catch (\Throwable) {
            return 0;
        }

        if ($endDt <= $startDt) {
            $endDt = $endDt->modify('+1 day');
        }

        return (int) round(($endDt->getTimestamp() - $startDt->getTimestamp()) / 60);
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

    public function getDeduction(): DeductionStatistic
    {
        return $this->deduction;
    }

    public function getLeave(): LeaveStatistic
    {
        return $this->leave;
    }

    /** @return array<int, mixed> */
    public function getRules(): array
    {
        return $this->rules;
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
            'deduction' => $this->deduction->toArray(),
            'leave' => $this->leave->toArray(),
        ];
    }
}
