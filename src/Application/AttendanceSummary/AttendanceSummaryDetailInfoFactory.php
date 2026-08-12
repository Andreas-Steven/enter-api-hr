<?php

declare(strict_types=1);

namespace App\Application\AttendanceSummary;

use App\Application\AttendanceSummary\Dto\AttendanceSummaryDetailInfo;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Domain\Employee\Entity\Employee;
use App\Domain\Shared\ValueObject\DetailInfo;

/**
 * Builds the AttendanceSummary detail_info payload and attendance detail
 * purely from the Employee aggregate snapshot and attendance records.
 * No external branch/work-pattern module calls.
 */
final class AttendanceSummaryDetailInfoFactory
{
    public function __construct(
        private AttendanceRepositoryInterface $attendanceRepository,
    ) {}

    public function createFromEmployee(Employee $employee, int $period): AttendanceSummaryDetailInfo
    {
        $branch = $employee->toBranchSnapshot();
        $division = $employee->toDivisionSnapshot();
        $position = $employee->getDetailInfo()->get('position') ?? [];
        $employeeType = $employee->getDetailInfo()->get('employee_type') ?? [];
        $workPattern = $employee->toWorkScheduleSnapshot();

        return new AttendanceSummaryDetailInfo(
            employeeId: $employee->getId(),
            employeeName: $employee->getName(),
            branchId: $branch['id'] ?? null,
            branchCode: $branch['code'] ?? null,
            branchName: $branch['name'] ?? null,
            divisionId: $division['id'] ?? null,
            divisionCode: $division['code'] ?? null,
            divisionName: $division['name'] ?? null,
            positionId: $position['id'] ?? $employee->getPositionId(),
            positionName: $position['name'] ?? null,
            employeeTypeId: $employeeType['id'] ?? $employee->getEmployeeTypeId(),
            employeeTypeName: $employeeType['name'] ?? null,
            workPatternId: $workPattern['id'] ?? null,
            workPatternName: $workPattern['name'] ?? null,
        );
    }

    public function toDetailInfo(AttendanceSummaryDetailInfo $dto): DetailInfo
    {
        return DetailInfo::fromArray($dto->toArray());
    }

    /**
     * Aggregates and transforms attendance records for a single employee into
     * a sorted attendance detail array suitable for summary storage.
     *
     * @return array<int, array>
     */
    public function buildAttendanceDetail(int $employeeId, int $period): array
    {
        $attendanceRecords = $this->attendanceRepository->findByPeriod($period);

        $attendanceDetail = [];
        foreach ($attendanceRecords as $record) {
            $date = $record->getDate();
            foreach ($record->getAttendance() as $item) {
                $itemData = is_array($item) ? $item : $item->toArray();
                if ((int)($itemData['employee_id'] ?? 0) !== $employeeId) {
                    continue;
                }

                // Extract work_pattern_rule from the daily schedule snapshot
                // before stripping work_pattern (which is bulky).
                $workPattern = $itemData['work_pattern'] ?? [];
                $daySchedule = $workPattern['working_days'][0] ?? [];
                $rule = $daySchedule['work_pattern_rule'] ?? null;

                // Extract is_obligation_day from info (set during evaluation)
                $info = $itemData['info'] ?? [];
                $isObligationDay = $info['is_obligation_day'] ?? false;
                $ruleFromInfo = $info['work_pattern_rule'] ?? null;

                unset(
                    $itemData['attendance_policies'],
                    $itemData['device'],
                    $itemData['branch'],
                    $itemData['division'],
                    $itemData['work_pattern'],
                    $itemData['location'],
                );

                // Clean info of fields that are redundant with the top-level fields
                unset($info['work_pattern_rule'], $info['is_obligation_day']);

                $attendanceDetail[] = [
                    'date' => $date,
                    'start_time' => $itemData['start_time'] ?? null,
                    'end_time' => $itemData['end_time'] ?? null,
                    'info' => $info,
                    'work_duration' => $itemData['work_duration'] ?? null,
                    'is_obligation_day' => $isObligationDay,
                    'work_pattern_rule' => $ruleFromInfo ?? $rule,
                ];
            }
        }

        usort($attendanceDetail, static fn(array $a, array $b): int => strcmp($a['date'], $b['date']));

        return $attendanceDetail;
    }
}
