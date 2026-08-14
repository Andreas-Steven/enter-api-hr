<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary\Dto;

use App\Domain\AttendanceSummary\Entity\AttendanceSummary;

final class AttendanceSummaryResponseMapper
{
    public static function fromEntity(AttendanceSummary $entity): array
    {
        $detailInfo = $entity->getDetailInfo()->toArray();
        $attendanceDetail = $entity->getAttendanceDetail();
        $workSchedule = self::workSchedule(
            workPatternId: $entity->getWorkPatternId(),
            detailInfo: $detailInfo,
            attendanceDetail: $attendanceDetail,
        );

        return [
            'id' => (int) $entity->getId(),
            'name' => $entity->getName(),
            'period' => $entity->getPeriod(),
            'status' => $entity->getStatus()->value(),
            'employee' => self::employee(
                detailInfo: $detailInfo,
                employeeId: $entity->getEmployeeId(),
                branchId: $entity->getBranchId(),
                divisionId: $entity->getDivisionId(),
                positionId: $entity->getPositionId(),
                employeeTypeId: $entity->getEmployeeTypeId(),
            ),
            'work_schedule' => $workSchedule,
            'attendance' => [
                'daily' => self::daily(
                    attendanceDetail: $attendanceDetail,
                    workSchedule: $workSchedule,
                ),
            ],
            'permissions' => self::permissions($entity->getPermissionDetail()),
            'summary' => self::summary(
                periodSummary: $entity->getPeriodSummary(),
                timeSummary: $entity->getTimeSummary(),
            ),
            'policy' => self::policy($detailInfo),
            'detail_info' => self::onlyChangeLog($detailInfo),
            'notes' => $entity->getNotes(),
            'sync_mdb' => $entity->getSyncMdbValue(),
            'lock_version' => $entity->getLockVersion()->value(),
        ];
    }

    public static function fromListRow(array $row): array
    {
        $detailInfo = self::arrayValue($row['detail_info'] ?? null);
        $employee = self::employee(
            detailInfo: $detailInfo,
            employeeId: self::intOrNull($row['employee_id'] ?? null),
            branchId: null,
            divisionId: null,
            positionId: null,
            employeeTypeId: null,
        );

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'period' => self::intOrNull($row['period'] ?? null),
            'status' => (int) ($row['status'] ?? 0),
            'employee' => [
                'id' => $employee['id'],
                'name' => $employee['name'],
            ],
            'summary' => self::compactSummary(
                periodSummary: self::arrayValue($row['period_summary'] ?? null),
                timeSummary: self::arrayValue($row['time_summary'] ?? null),
            ),
            'detail_info' => self::onlyChangeLog($detailInfo),
        ];
    }

    private static function employee(
        array $detailInfo,
        ?int $employeeId,
        ?int $branchId,
        ?int $divisionId,
        ?int $positionId,
        ?int $employeeTypeId,
    ): array {
        $employee = self::arrayValue($detailInfo['employee'] ?? null);
        $branch = self::arrayValue($detailInfo['branch'] ?? null);
        $division = self::arrayValue($detailInfo['division'] ?? null);
        $position = self::arrayValue($detailInfo['position'] ?? null);
        $employeeType = self::arrayValue($detailInfo['employee_type'] ?? null);

        return [
            'id' => self::intOrNull($employee['id'] ?? $employeeId),
            'name' => $employee['name'] ?? null,
            'type' => [
                'id' => self::intOrNull($employeeType['id'] ?? $employeeTypeId),
                'name' => $employeeType['name'] ?? null,
            ],
            'position' => [
                'id' => self::intOrNull($position['id'] ?? $positionId),
                'name' => $position['name'] ?? null,
            ],
            'division' => [
                'id' => self::intOrNull($division['id'] ?? $divisionId),
                'code' => $division['code'] ?? null,
                'name' => $division['name'] ?? null,
            ],
            'branch' => [
                'id' => self::intOrNull($branch['id'] ?? $branchId),
                'code' => $branch['code'] ?? null,
                'name' => $branch['name'] ?? null,
            ],
        ];
    }

    private static function workSchedule(
        ?int $workPatternId,
        array $detailInfo,
        array $attendanceDetail,
    ): array {
        $branch = self::arrayValue($detailInfo['branch'] ?? null);
        $workPattern = self::arrayValue($detailInfo['work_pattern'] ?? null);
        if ($workPattern === []) {
            $workPattern = self::arrayValue($branch['work_pattern'] ?? null);
        }

        $workingDays = self::arrayList($workPattern['working_days'] ?? []);
        $weeklyByDay = [];
        $rulesById = [];
        $shiftsById = [];

        foreach ($workingDays as $day) {
            $dayOfWeek = self::intOrNull($day['day_of_week'] ?? null);
            if ($dayOfWeek === null) {
                continue;
            }

            $shift = self::arrayValue($day['shift'] ?? null);
            $rule = self::arrayValue($day['work_pattern_rule'] ?? null);
            $shiftId = self::intOrNull($shift['id'] ?? null);
            $ruleId = self::intOrNull($rule['id'] ?? null);

            if ($shiftId !== null) {
                $shiftsById[(string) $shiftId] = self::shift($shift);
            }
            self::addRule($rulesById, $rule);

            $weeklyByDay[(string) $dayOfWeek] = [
                'day_of_week' => $dayOfWeek,
                'shift_id' => $shiftId,
                'rule_id' => $ruleId,
            ];
        }

        foreach (self::arrayList($attendanceDetail) as $item) {
            $rule = self::extractRule($item);
            self::addRule($rulesById, $rule);

            $date = (string) ($item['date'] ?? '');
            $dayOfWeek = self::dayOfWeek($date);
            if ($dayOfWeek === null || isset($weeklyByDay[(string) $dayOfWeek])) {
                continue;
            }

            $weeklyByDay[(string) $dayOfWeek] = [
                'day_of_week' => $dayOfWeek,
                'shift_id' => self::extractShiftId($item),
                'rule_id' => self::intOrNull($rule['id'] ?? null),
            ];
        }

        ksort($weeklyByDay, SORT_NUMERIC);
        $shift = array_values($shiftsById)[0] ?? [];

        return [
            'pattern' => [
                'id' => self::intOrNull($workPattern['id'] ?? $workPatternId),
                'name' => $workPattern['name'] ?? null,
            ],
            'shift' => $shift,
            'rules' => array_values($rulesById),
            'weekly_schedule' => array_values($weeklyByDay),
        ];
    }

    private static function daily(array $attendanceDetail, array $workSchedule): array
    {
        $weeklyByDay = [];
        foreach ($workSchedule['weekly_schedule'] ?? [] as $day) {
            if (isset($day['day_of_week'])) {
                $weeklyByDay[(string) $day['day_of_week']] = $day;
            }
        }

        $daily = [];
        foreach (self::arrayList($attendanceDetail) as $item) {
            $info = self::arrayValue($item['info'] ?? null);
            $evaluation = self::arrayValue($info['detail'] ?? null);
            $date = (string) ($item['date'] ?? '');
            $dayOfWeek = self::dayOfWeek($date);
            $weekly = $dayOfWeek !== null
                ? ($weeklyByDay[(string) $dayOfWeek] ?? [])
                : [];
            $rule = self::extractRule($item);
            $ruleId = self::intOrNull($rule['id'] ?? null)
                ?? self::intOrNull($weekly['rule_id'] ?? null);
            $shiftId = self::extractShiftId($item)
                ?? self::intOrNull($weekly['shift_id'] ?? null);

            $isObligationDay = self::field(
                $item,
                'is_obligation_day',
                self::field($info, 'is_obligation_day'),
            );

            $earlyInMinutes = self::intOrNull(
                self::firstField($evaluation, ['early_in_minutes', 'early_in']),
            );
            $earlyOutMinutes = self::intOrNull(
                self::firstField($evaluation, ['early_out_minutes', 'early_out']),
            );

            $daily[] = [
                'date' => $date,
                'schedule' => [
                    'day_of_week' => $dayOfWeek,
                    'shift_id' => $shiftId,
                    'rule_id' => $ruleId,
                    'is_obligation_day' => self::boolOrNull($isObligationDay),
                ],
                'actual' => [
                    'start_time' => self::field($item, 'start_time'),
                    'end_time' => self::field($item, 'end_time'),
                    'work_duration_minutes' => self::intOrNull(
                        self::firstField($item, ['work_duration_minutes', 'work_duration']),
                    ),
                ],
                'evaluation' => [
                    'status' => self::dailyStatus($info, $evaluation),
                    'late' => self::boolOrNull(self::field($evaluation, 'late', false)),
                    'late_minutes' => self::intOrNull(self::field($evaluation, 'late_minutes', 0)),
                    'early_in' => $earlyInMinutes === null ? null : $earlyInMinutes > 0,
                    'early_in_minutes' => $earlyInMinutes,
                    'early_out' => $earlyOutMinutes === null ? null : $earlyOutMinutes > 0,
                    'early_out_minutes' => $earlyOutMinutes,
                    'missing_in' => self::boolOrNull(self::field($evaluation, 'missing_in', false)),
                    'missing_out' => self::boolOrNull(self::field($evaluation, 'missing_out', false)),
                ],
            ];
        }

        return $daily;
    }

    private static function permissions(array $permissionDetail): array
    {
        $permissions = [];
        foreach (self::arrayList($permissionDetail) as $permission) {
            $type = self::arrayValue($permission['permission'] ?? null);
            $startDate = self::field($permission, 'start_date');
            $totalDays = self::intOrNull(
                self::firstField($permission, ['total_days', 'total_day']),
            );
            $periodDays = self::intOrNull(
                self::firstField($permission, ['period_days', 'period_day_count']),
            );
            $endDate = self::field($permission, 'end_date');

            if ($endDate === null && $startDate !== null && $totalDays === 1 && $periodDays === 1) {
                $endDate = $startDate;
            }

            $permissions[] = [
                'id' => self::intOrNull($permission['id'] ?? null),
                'type' => [
                    'id' => self::intOrNull(
                        $type['id'] ?? self::field($permission, 'permission_type'),
                    ),
                    'name' => $type['name'] ?? null,
                ],
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $totalDays,
                'period_days' => $periodDays,
                'status' => self::field($permission, 'status'),
                'approved' => self::field($permission, 'approved'),
                'note' => self::field($permission, 'note'),
                'approved_note' => self::field($permission, 'approved_note'),
                'employee_leave_id' => self::intOrNull(
                    self::field($permission, 'employee_leave_id'),
                ),
            ];
        }

        return $permissions;
    }

    private static function compactSummary(array $periodSummary, array $timeSummary): array
    {
        $summary = self::summary(
            periodSummary: $periodSummary,
            timeSummary: $timeSummary,
        );

        return [
            'attendance' => [
                'obligation_days' => $summary['attendance']['obligation_days'],
                'present_days' => $summary['attendance']['present_days'],
                'absent_days' => $summary['attendance']['absent_days'],
                'sick_days' => $summary['attendance']['sick_days'],
                'leave_days' => $summary['attendance']['leave_days'],
            ],
            'compliance' => [
                'unfulfilled' => $summary['compliance']['unfulfilled'],
            ],
            'time' => [
                'late' => $summary['time']['late'],
            ],
        ];
    }

    private static function summary(array $periodSummary, array $timeSummary): array
    {
        $rawRules = self::arrayList($periodSummary['rule_breakdown'] ?? []);
        $rules = array_map(
            static fn(array $rule): array => self::ruleResult($rule),
            $rawRules,
        );

        $fulfilled = 0;
        foreach ($rules as $rule) {
            if ($rule['fulfilled'] !== null) {
                $fulfilled += $rule['fulfilled'];
            }
        }
        if ($rules === []) {
            $fulfilled = self::intOrNull(
                self::firstField($periodSummary, ['fulfilled_count', 'valid_present_count'], 0),
            ) ?? 0;
        }

        return [
            'attendance' => [
                'obligation_days' => self::intOrNull(
                    self::firstField($periodSummary, ['obligation_total_count', 'hek_count']),
                ),
                'present_days' => self::intOrNull(
                    self::firstField($periodSummary, ['present_days', 'hek_count', 'valid_present_count']),
                ),
                'absent_days' => self::intOrNull(self::field($periodSummary, 'absent_days', 0)),
                'sick_days' => self::intOrNull(self::field($periodSummary, 'sick_days', 0)),
                'leave_days' => self::intOrNull(self::field($periodSummary, 'leave_days', 0)),
                'permission_days' => self::intOrNull(self::field($periodSummary, 'permission_days', 0)),
            ],
            'compliance' => [
                'fulfilled' => $fulfilled,
                'unfulfilled' => self::intOrNull(
                    self::field($periodSummary, 'obligation_unfulfilled_count', 0),
                ),
                'deficit' => self::intOrNull(self::field($periodSummary, 'deficit_count', 0)),
                'excess' => self::intOrNull(self::field($periodSummary, 'excess_count', 0)),
                'pending_review' => self::intOrNull(
                    self::field($periodSummary, 'pending_review_count', 0),
                ),
            ],
            'rules' => $rules,
            'time' => [
                'late' => [
                    'count' => self::intOrNull(self::field($timeSummary, 'late_count', 0)),
                    'minutes' => self::intOrNull(self::field($timeSummary, 'late_min_total', 0)),
                ],
                'early_out' => [
                    'count' => self::intOrNull(self::field($timeSummary, 'early_out_count', 0)),
                    'minutes' => self::intOrNull(self::field($timeSummary, 'early_out_min_total', 0)),
                ],
                'incomplete' => self::intOrNull(self::field($timeSummary, 'incomplete_count', 0)),
            ],
            'overtime' => [
                'days' => self::intOrNull(self::field($periodSummary, 'overtime_day_count', 0)),
                'minutes' => self::intOrNull(self::field($timeSummary, 'overtime_min_total', 0)),
            ],
            'deduction' => [
                'days' => self::intOrNull(self::field($periodSummary, 'deduction_day_count', 0)),
            ],
            'leave' => [
                'quota_used' => self::intOrNull(
                    self::field($periodSummary, 'leave_quota_used_count', 0),
                ),
            ],
        ];
    }

    private static function ruleResult(array $rule): array
    {
        return [
            'rule_id' => self::intOrNull(
                self::firstField($rule, ['rule_id', 'id']),
            ),
            'target' => self::intOrNull(
                self::firstField($rule, ['target', 'target_count']),
            ),
            'fulfilled' => self::intOrNull(
                self::firstField($rule, ['fulfilled', 'fulfilled_count'], 0),
            ),
            'deficit' => self::intOrNull(
                self::firstField($rule, ['deficit', 'deficit_count'], 0),
            ),
            'excess' => self::intOrNull(
                self::firstField($rule, ['excess', 'excess_count'], 0),
            ),
        ];
    }

    private static function policy(array $detailInfo): array
    {
        $branch = self::arrayValue($detailInfo['branch'] ?? null);
        $policy = self::arrayValue($detailInfo['policy'] ?? null);
        if ($policy === []) {
            $policy = self::arrayValue($branch['attendance_policies'] ?? null);
        }
        $policyDetail = self::arrayValue($policy['policy_detail'] ?? null);

        return [
            'id' => self::intOrNull($policy['id'] ?? null),
            'name' => $policy['name'] ?? null,
            'late_grace_in' => self::field(
                $policyDetail,
                'late_grace_in',
                self::field($policy, 'late_grace_in'),
            ),
            'early_grace_out' => self::field(
                $policyDetail,
                'early_grace_out',
                self::field($policy, 'early_grace_out'),
            ),
            'min_work_full_day' => self::field(
                $policyDetail,
                'min_work_full_day',
                self::field($policy, 'min_work_full_day'),
            ),
            'min_work_half_day' => self::field(
                $policyDetail,
                'min_work_half_day',
                self::field($policy, 'min_work_half_day'),
            ),
            'missing_in_policy' => self::field(
                $policyDetail,
                'missing_in_policy',
                self::field($policy, 'missing_in_policy'),
            ),
            'missing_out_policy' => self::field(
                $policyDetail,
                'missing_out_policy',
                self::field($policy, 'missing_out_policy'),
            ),
        ];
    }

    private static function extractRule(array $item): array
    {
        $rule = self::arrayValue($item['work_pattern_rule'] ?? null);
        if ($rule !== []) {
            return $rule;
        }

        $info = self::arrayValue($item['info'] ?? null);
        $rule = self::arrayValue($info['work_pattern_rule'] ?? null);
        if ($rule !== []) {
            return $rule;
        }

        $workPattern = self::arrayValue($item['work_pattern'] ?? null);
        $date = (string) ($item['date'] ?? '');
        $dayOfWeek = self::dayOfWeek($date);
        foreach (self::arrayList($workPattern['working_days'] ?? []) as $day) {
            if (self::intOrNull($day['day_of_week'] ?? null) === $dayOfWeek) {
                return self::arrayValue($day['work_pattern_rule'] ?? null);
            }
        }

        return [];
    }

    private static function extractShiftId(array $item): ?int
    {
        $shiftId = self::intOrNull($item['shift_id'] ?? null);
        if ($shiftId !== null) {
            return $shiftId;
        }

        $workPattern = self::arrayValue($item['work_pattern'] ?? null);
        $date = (string) ($item['date'] ?? '');
        $dayOfWeek = self::dayOfWeek($date);
        foreach (self::arrayList($workPattern['working_days'] ?? []) as $day) {
            if (self::intOrNull($day['day_of_week'] ?? null) !== $dayOfWeek) {
                continue;
            }

            $shift = self::arrayValue($day['shift'] ?? null);
            return self::intOrNull($shift['id'] ?? null);
        }

        return null;
    }

    private static function addRule(array &$rulesById, array $rule): void
    {
        if ($rule === []) {
            return;
        }

        $normalized = self::rule($rule);
        if ($normalized['id'] === null) {
            return;
        }

        $key = (string) $normalized['id'];
        if (!isset($rulesById[$key])) {
            $rulesById[$key] = $normalized;
            return;
        }

        foreach ($normalized as $field => $value) {
            if (
                ($rulesById[$key][$field] === null || $rulesById[$key][$field] === [])
                && $value !== null
                && $value !== []
            ) {
                $rulesById[$key][$field] = $value;
            }
        }
    }

    private static function rule(array $rule): array
    {
        $holidayTypes = self::firstField($rule, ['holiday_types', 'holiday_type'], []);
        if (!is_array($holidayTypes)) {
            $holidayTypes = [];
        }

        return [
            'id' => self::intOrNull($rule['id'] ?? null),
            'name' => $rule['name'] ?? null,
            'type' => self::firstField($rule, ['type', 'rule_type']),
            'holiday_types' => $holidayTypes,
            'holiday_wins' => self::field($rule, 'holiday_wins'),
            'target_count' => self::field($rule, 'target_count'),
            'excess_treatment' => self::field($rule, 'excess_treatment'),
            'deficit_treatment' => self::field($rule, 'deficit_treatment'),
            'substitution_allowed' => self::field($rule, 'substitution_allowed'),
        ];
    }

    private static function shift(array $shift): array
    {
        return [
            'id' => self::intOrNull($shift['id'] ?? null),
            'name' => $shift['name'] ?? null,
            'start_time' => $shift['start_time'] ?? null,
            'end_time' => $shift['end_time'] ?? null,
        ];
    }

    private static function dailyStatus(array $info, array $evaluation): ?string
    {
        $summary = self::arrayValue($info['summary'] ?? []);
        if (isset($summary[0]) && is_string($summary[0])) {
            return strtoupper($summary[0]);
        }
        if (self::boolOrNull($evaluation['full_day'] ?? null) === true) {
            return 'FULL_DAY';
        }
        if (self::boolOrNull($evaluation['half_day'] ?? null) === true) {
            return 'HALF_DAY';
        }

        return null;
    }

    private static function arrayValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private static function onlyChangeLog(array $detailInfo): array
    {
        $changeLog = array_key_exists('change_log', $detailInfo)
            ? $detailInfo['change_log']
            : ($detailInfo['audit'] ?? null);

        return [
            'change_log' => $changeLog,
        ];
    }

    private static function arrayList(mixed $value): array
    {
        $value = self::arrayValue($value);

        return array_values(array_filter(
            $value,
            static fn(mixed $item): bool => is_array($item),
        ));
    }

    private static function field(array $data, string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    private static function firstField(array $data, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return $default;
    }

    private static function intOrNull(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private static function boolOrNull(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }

    private static function dayOfWeek(string $date): ?int
    {
        if ($date === '') {
            return null;
        }

        $timestamp = strtotime($date);

        return $timestamp === false ? null : (int) date('N', $timestamp);
    }
}
