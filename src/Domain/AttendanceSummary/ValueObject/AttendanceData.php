<?php

declare(strict_types=1);

namespace App\Domain\AttendanceSummary\ValueObject;

/**
 * Daily attendance entries backing a summary.
 *
 * Stored as JSON in the `attendance` column as a flat list of per-day entries.
 * Each entry is one (date => raw attendance item) pair captured for the
 * employee during the period. The full per-day `info` (in/out, summary,
 * detail), the `is_obligation_day` flag and the `work_pattern_rule` are
 * preserved so that downstream consumers (e.g. PayrollReport generation) can
 * re-evaluate incidents without reloading the source attendance.
 */
final readonly class AttendanceData
{
    private function __construct(
        private array $daily,
    ) {}

    public static function empty(): self
    {
        return new self([]);
    }

    public static function fromArray(array $data): self
    {
        return new self($data['daily'] ?? $data);
    }

    /**
     * Permission types that excuse an absent obligation day (no ABSENT
     * deduction should be generated). Mirrors the excused types in
     * {@see \App\Domain\AttendanceSummary\ValueObject\Summary::compute()}.
     */
    private const EXCUSED_PERMISSION_TYPES = [
        1, // SICK
        2, // SICK_WITH_RECEIPT
        5, // ANNUAL_LEAVE
        6, // PERMISSION_LEAVE
        7, // UNPAID_LEAVE
    ];

    /**
     * Builds the daily records from raw attendance item arrays keyed by date.
     *
     * When a WorkSchedule and holiday dates are provided, the `is_obligation_day`
     * flag and `work_pattern_rule` are determined from the schedule (overriding
     * any values in the raw item's info). This ensures the snapshot reflects
     * the same obligation logic used by the summary calculation.
     *
     * When $periodDays and $permissionDateMap are provided, absent obligation
     * days (REQUIRED_WEEKDAY days with no attendance record and no approved
     * sick/leave permission) are also injected so that downstream consumers
     * (e.g. PayrollReport ABSENT deduction generation) can detect them.
     *
     * @param array<string, array> $rawDaily Map of date (Y-m-d) => raw item array.
     * @param WorkSchedule|null $workSchedule Captured work schedule (optional).
     * @param array<string, int> $holidayDates Map of holiday date (Y-m-d) => day_off value.
     * @param list<string> $periodDays Every calendar day in the period range.
     * @param array<string, int> $permissionDateMap Map of date (Y-m-d) => PermissionType value for approved permissions.
     */
    public static function fromRawDailyItems(
        array $rawDaily,
        ?WorkSchedule $workSchedule = null,
        array $holidayDates = [],
        array $periodDays = [],
        array $permissionDateMap = [],
    ): self {
        $daily = [];

        // Track PERIODIC_REQUIRED_DAY counts so we can flag the first
        // target_count attended days as obligation days.
        $periodicCounts = [];

        foreach ($rawDaily as $date => $raw) {
            $daily[] = self::serializeDay(
                $date,
                $raw,
                $workSchedule,
                $holidayDates,
                $periodicCounts,
            );
        }

        // Inject absent obligation days: REQUIRED_WEEKDAY (HEK) days in the
        // period that have no attendance record and no approved sick/leave
        // permission. PERIODIC_REQUIRED_DAY days are only obligation when
        // attended (per Summary::compute), so they are never injected here.
        if ($workSchedule !== null && $periodDays !== []) {
            $attendedDates = array_keys($rawDaily);

            foreach ($periodDays as $date) {
                if (in_array($date, $attendedDates, true)) {
                    continue;
                }

                $day = $workSchedule->findDayForDate($date);
                if ($day === null || ($day['shift'] ?? null) === null) {
                    continue;
                }

                $rule = $day['work_pattern_rule'] ?? null;
                $ruleType = $rule['rule_type'] ?? null;
                $holidayWins = (int) ($rule['holiday_wins'] ?? 0);

                // Only REQUIRED_WEEKDAY days can be absent obligation days.
                if ($ruleType !== 'REQUIRED_WEEKDAY') {
                    continue;
                }

                // Skip holidays when holiday_wins is set.
                if (isset($holidayDates[$date]) && $holidayWins === 1) {
                    continue;
                }

                // Skip days excused by approved sick/leave permission.
                $permissionType = $permissionDateMap[$date] ?? null;
                if ($permissionType !== null
                    && in_array((int) $permissionType, self::EXCUSED_PERMISSION_TYPES, true)) {
                    continue;
                }

                $daily[] = self::serializeAbsentDay($date, $rule);
            }
        }

        // Keep days chronologically ordered.
        usort($daily, static fn(array $a, array $b): int => $a['date'] <=> $b['date']);

        return new self($daily);
    }

    /**
     * Serializes an absent obligation day (no attendance record) into the
     * daily entry shape expected by downstream consumers.
     *
     * @param array<string, mixed> $rule The work pattern rule for this day.
     *
     * @return array<string, mixed>
     */
    private static function serializeAbsentDay(string $date, array $rule): array
    {
        return [
            'date' => $date,
            'start_time' => null,
            'end_time' => null,
            'work_duration' => null,
            'is_obligation_day' => true,
            'work_pattern_rule' => $rule,
            'info' => [
                'in' => false,
                'out' => false,
                'summary' => ['absent'],
                'detail' => [],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $raw
     * @param array<string, int> $holidayDates
     * @param array<int, int> $periodicCounts
     *
     * @return array<string, mixed>
     */
    private static function serializeDay(
        string $date,
        array $raw,
        ?WorkSchedule $workSchedule,
        array $holidayDates,
        array &$periodicCounts,
    ): array {
        $info = $raw['info'] ?? [];

        // Determine is_obligation_day and work_pattern_rule from the schedule.
        $isObligationDay = false;
        $workPatternRule = $info['work_pattern_rule'] ?? null;

        if ($workSchedule !== null) {
            $day = $workSchedule->findDayForDate($date);

            if ($day !== null && ($day['shift'] ?? null) !== null) {
                $rule = $day['work_pattern_rule'] ?? null;
                $ruleType = $rule['rule_type'] ?? null;
                $holidayWins = (int) ($rule['holiday_wins'] ?? 0);
                $isHoliday = isset($holidayDates[$date]);

                $workPatternRule = $rule;

                if (!($isHoliday && $holidayWins === 1)) {
                    if ($ruleType === 'PERIODIC_REQUIRED_DAY') {
                        $ruleId = (int) ($rule['id'] ?? 0);
                        $targetCount = (int) ($rule['target_count'] ?? 0);
                        $current = $periodicCounts[$ruleId] ?? 0;

                        if ($targetCount === 0 || $current < $targetCount) {
                            $isObligationDay = true;
                            $periodicCounts[$ruleId] = $current + 1;
                        }
                    } else {
                        $isObligationDay = true;
                    }
                }
            }
        } else {
            $isObligationDay = $info['is_obligation_day'] ?? false;
        }

        return [
            'date' => $date,
            'start_time' => $raw['start_time'] ?? null,
            'end_time' => $raw['end_time'] ?? null,
            'work_duration' => $raw['work_duration'] ?? $raw['total_work_minutes'] ?? null,
            'is_obligation_day' => $isObligationDay,
            'work_pattern_rule' => $workPatternRule,
            'info' => [
                'in' => $info['in'] ?? false,
                'out' => $info['out'] ?? false,
                'summary' => $info['summary'] ?? [],
                'detail' => $info['detail'] ?? [],
            ],
        ];
    }

    /** @return list<array<string, mixed>> */
    public function toArray(): array
    {
        return $this->daily;
    }
}
