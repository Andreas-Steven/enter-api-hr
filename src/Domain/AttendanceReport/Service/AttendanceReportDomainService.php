<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReport\Service;

// Domain Layer
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Domain\AttendanceCalendar\Repository\AttendanceCalendarRepositoryInterface;
use App\Domain\AttendanceReport\Repository\AttendanceReportRepositoryInterface;
use App\Domain\AttendanceReport\ValueObject\Name;
use App\Domain\AttendanceReport\ValueObject\ReportData;
use App\Domain\AttendanceReport\ValueObject\Summary;
use App\Domain\AttendancePeriod\Repository\AttendancePeriodRepositoryInterface;
use App\Domain\Employee\Entity\Employee;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\AttendancePeriod\ValueObject\PayrollPeriod;

use DateTimeImmutable;

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
        private AttendanceCalendarRepositoryInterface $attendanceCalendarRepository,
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
        $payrollPeriod = PayrollPeriod::fromInt($period);

        // AttendancePeriod entity is optional enrichment — if it exists,
        // use its custom date range; otherwise derive from PayrollPeriod.
        $attendancePeriod = $this->attendancePeriodRepository->findByPeriod($period);

        $startDate = $attendancePeriod?->getStartDate()?->toDateTime() ?? $payrollPeriod->startDate();
        $endDate = $attendancePeriod?->getEndDate()?->toDateTime() ?? $payrollPeriod->endDate();

        $employees = $this->employeeRepository->findAllActive();
        if (empty($employees)) {
            return [];
        }

        $attendanceRecords = $this->attendanceRepository->findByPeriod($period);
        $calendars = $this->attendanceCalendarRepository->findByPeriod($period);

        $summaries = [];
        foreach ($employees as $employee) {
            $summaries[$employee->getId()] = $this->buildReportData(
                employee: $employee,
                attendanceRecords: $attendanceRecords,
                startDate: $startDate,
                endDate: $endDate,
                calendars: $calendars,
                period: $period,
            );
        }

        return $summaries;
    }

    /**
     * @param array<int, \App\Domain\Attendance\Entity\Attendance> $attendanceRecords
     * @param array<int, \App\Domain\AttendanceCalendar\Entity\AttendanceCalendar> $calendars
     */
    private function buildReportData(
        Employee $employee,
        array $attendanceRecords,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $calendars,
        int $period,
    ): ReportData {
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
                startDate: $startDate,
                endDate: $endDate,
                calendars: $calendars,
            ),
            reportType: 'HEK',
            employee: $employee,
        );
    }
}
