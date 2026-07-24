<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReport\Service;

// Domain Layer
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Domain\AttendanceReport\Repository\AttendanceReportRepositoryInterface;
use App\Domain\AttendanceReport\ValueObject\Name;
use App\Domain\AttendanceReport\ValueObject\ReportData;
use App\Domain\AttendanceReport\ValueObject\Summary;
use App\Domain\AttendancePeriod\Repository\AttendancePeriodRepositoryInterface;
use App\Domain\Employee\Entity\Employee;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;

// Shared Layer
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendanceReport Domain Service
 *
 * Pure business logic and domain rules. Generates per-employee, per-period
 * attendance summaries by aggregating daily Attendance records.
 */
final class AttendanceReportDomainService
{
    use DomainValidator;

    public function __construct(
        private AttendanceReportRepositoryInterface $repository,
        private AttendancePeriodRepositoryInterface $attendancePeriodRepository,
        private EmployeeRepositoryInterface $employeeRepository,
        private AttendanceRepositoryInterface $attendanceRepository,
    ) {}

    /**
     * Generate report data for every active employee in the given period.
     *
     * @return array<int, ReportData> Array keyed by employee_id containing report payloads.
     */
    public function generateSummariesForPeriod(int $period): array
    {
        $attendancePeriod = $this->attendancePeriodRepository->findByPeriod($period);
        if ($attendancePeriod === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'AttendancePeriod',
                        'field' => 'period',
                        'value' => $period,
                    ]
                )
            );
        }

        $employees = $this->employeeRepository->findAllActive();
        if (empty($employees)) {
            return [];
        }

        $attendanceRecords = $this->attendanceRepository->findByPeriod($period);

        $summaries = [];
        foreach ($employees as $employee) {
            $summaries[$employee->getId()] = $this->buildReportData(
                employee: $employee,
                attendanceRecords: $attendanceRecords,
                period: $period,
            );
        }

        return $summaries;
    }

    /**
     * @param array<int, \App\Domain\Attendance\Entity\Attendance> $attendanceRecords
     */
    private function buildReportData(Employee $employee, array $attendanceRecords, int $period): ReportData
    {
        $workPattern = $employee->toWorkScheduleSnapshot();
        $workPatternId = (int) ($workPattern['id'] ?? 0);
        $branchSnapshot = $employee->toBranchSnapshot();
        $branchId = (int) ($branchSnapshot['id'] ?? 0);

        return new ReportData(
            name: Name::fromEmployeeAndPeriod(
                employeeName: $employee->getName(),
                employeeId: $employee->getId(),
                period: $period,
            )->getValue(),
            period: $period,
            employeeId: $employee->getId(),
            branchId: $branchId,
            divisionId: $employee->getDivisionId() ?? 0,
            positionId: $employee->getPositionId() ?? 0,
            employeeTypeId: $employee->getEmployeeTypeId() ?? 0,
            workPatternId: $workPatternId,
            summary: Summary::fromEmployeeAndAttendance(
                employee: $employee,
                period: $period,
                attendanceRecords: $attendanceRecords,
            ),
            reportType: 'HEK',
            employee: $employee,
        );
    }
}
