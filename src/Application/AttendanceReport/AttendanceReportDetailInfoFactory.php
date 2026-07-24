<?php

declare(strict_types=1);

namespace App\Application\AttendanceReport;

use App\Application\AttendanceReport\Dto\AttendanceReportDetailInfo;
use App\Domain\Employee\Entity\Employee;
use App\Domain\Shared\ValueObject\DetailInfo;

/**
 * Builds the AttendanceReport detail_info payload purely from the
 * Employee aggregate snapshot. No external branch/work-pattern module calls.
 */
final class AttendanceReportDetailInfoFactory
{
    public function createFromEmployee(Employee $employee, int $period): AttendanceReportDetailInfo
    {
        $branch = $employee->toBranchSnapshot();
        $division = $employee->toDivisionSnapshot();
        $position = $employee->getDetailInfo()->get('position') ?? [];
        $employeeType = $employee->getDetailInfo()->get('employee_type') ?? [];
        $workPattern = $employee->toWorkScheduleSnapshot();

        return new AttendanceReportDetailInfo(
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

    public function toDetailInfo(AttendanceReportDetailInfo $dto): DetailInfo
    {
        return DetailInfo::fromArray($dto->toArray());
    }
}
