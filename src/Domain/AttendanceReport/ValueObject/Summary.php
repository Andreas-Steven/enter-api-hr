<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReport\ValueObject;

use App\Domain\Attendance\Entity\Attendance;
use App\Domain\AttendanceCalendar\Entity\AttendanceCalendar;
use App\Domain\Employee\Entity\Employee;
use App\Domain\Shared\Enums\AttendanceStatus;
use App\Domain\Shared\Enums\HolidayType;

use DatePeriod;
use DateTimeImmutable;

/**
 * Value object representing the aggregated summary JSONB payload of an
 * AttendanceReport. Encapsulates HEK, attendance, and payroll calculations.
 */
final readonly class Summary
{
    private function __construct(
        private array $hek,
        private array $attendance,
        private array $payroll,
    ) {}

    public static function createDefault(): self
    {
        return new self(
            hek: [
                'total_days' => 0,
                'off_days' => 0,
                'national_holidays' => 0,
                'company_holidays' => 0,
                'work_holidays' => 0,
                'effective_work_days' => 0.00,
            ],
            attendance: [
                'full_work_days' => 0,
                'half_work_days' => 0,
                'present_days' => 0.00,
                'absent_days' => 0.00,
                'late_days' => 0,
                'late_minutes' => 0,
                'early_out_days' => 0,
                'early_out_minutes' => 0,
                'leave_days' => 0.00,
                'overtime_days' => 0.00,
                'overtime_minutes' => 0,
            ],
            payroll: [
                'deficit_days' => 0.00,
                'excess_days' => 0.00,
                'deduction_days' => 0.00,
            ],
        );
    }

    /**
     * Build a summary by aggregating daily attendance records for one employee.
     *
     * @param Attendance[] $attendanceRecords
     * @param AttendanceCalendar[] $calendars
     */
    public static function fromEmployeeAndAttendance(
        Employee $employee,
        int $period,
        array $attendanceRecords,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $calendars,
    ): self {
        $summary = self::createDefault();
        $summary = $summary->withHekCalendar(
            employee: $employee,
            startDate: $startDate,
            endDate: $endDate,
            calendars: $calendars,
        );

        foreach ($attendanceRecords as $attendance) {
            $summary = $summary->withAttendanceDay($employee, $attendance);
        }

        return $summary->finalize();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            hek: $data['hek'] ?? [],
            attendance: $data['attendance'] ?? [],
            payroll: $data['payroll'] ?? [],
        );
    }

    public function getHek(): array
    {
        return $this->hek;
    }

    public function getAttendance(): array
    {
        return $this->attendance;
    }

    public function getPayroll(): array
    {
        return $this->payroll;
    }

    public function toArray(): array
    {
        return [
            'hek' => $this->hek,
            'attendance' => $this->attendance,
            'payroll' => $this->payroll,
        ];
    }

    private function withAttendanceDay(Employee $employee, Attendance $attendance): self
    {
        $hek = $this->hek;
        $attendanceSummary = $this->attendance;

        $item = $this->findEmployeeItem($attendance, $employee->getId());
        if ($item === null) {
            return new self(
                hek: $hek,
                attendance: $attendanceSummary,
                payroll: $this->payroll,
            );
        }

        $info = $item['info'] ?? [];
        $detail = $info['detail'] ?? [];

        if (!empty($detail['full_day'])) {
            $attendanceSummary['full_work_days']++;
        } elseif (!empty($detail['half_day'])) {
            $attendanceSummary['half_work_days']++;
        }

        if (!empty($detail['late'])) {
            $attendanceSummary['late_days']++;
            $attendanceSummary['late_minutes'] += (int) ($detail['late_minutes'] ?? 0);
        }

        $earlyOut = (int) ($detail['early_out'] ?? 0);
        if ($earlyOut > 0) {
            $attendanceSummary['early_out_days']++;
            $attendanceSummary['early_out_minutes'] += $earlyOut;
        }

        return new self(
            hek: $hek,
            attendance: $attendanceSummary,
            payroll: $this->payroll,
        );
    }

    /**
     * Calculate calendar-based HEK from the attendance period range, employee
     * work pattern, and branch-scoped holidays.
     *
     * @param AttendanceCalendar[] $calendars
     */
    private function withHekCalendar(
        Employee $employee,
        DateTimeImmutable $startDate,
        DateTimeImmutable $endDate,
        array $calendars,
    ): self {
        $hek = $this->hek;

        $branchId = $employee->getBranchId() ?? 0;
        $holidays = $this->indexHolidaysByDate($calendars, $branchId);

        $totalDays = 0;
        $offDays = 0;
        $nationalHolidays = 0;
        $companyHolidays = 0;
        $workHolidays = 0;

        $period = new DatePeriod(
            start: $startDate,
            interval: new \DateInterval('P1D'),
            end: $endDate->modify('+1 day'),
        );

        foreach ($period as $date) {
            /** @var DateTimeImmutable $date */
            $totalDays++;

            $dailySchedule = $employee->toDailyScheduleSnapshot($date->format('Y-m-d'));
            $schedule = $dailySchedule['working_days'][0] ?? null;
            $hasShift = !empty($schedule) && !empty($schedule['shift']);

            if (!$hasShift) {
                $offDays++;
                continue;
            }

            $dateKey = $date->format('Y-m-d');
            if (isset($holidays[$dateKey])) {
                $holidayType = $holidays[$dateKey];
                match ($holidayType) {
                    HolidayType::NATIONAL_HOLIDAY => $nationalHolidays++,
                    HolidayType::WEEKLY_HOLIDAY => $workHolidays++,
                    default => $companyHolidays++,
                };
                continue;
            }
        }

        $hek['total_days'] = $totalDays;
        $hek['off_days'] = $offDays;
        $hek['national_holidays'] = $nationalHolidays;
        $hek['company_holidays'] = $companyHolidays;
        $hek['work_holidays'] = $workHolidays;
        $hek['effective_work_days'] = round(
            $totalDays - $offDays - $nationalHolidays - $companyHolidays - $workHolidays,
            2
        );

        return new self(
            hek: $hek,
            attendance: $this->attendance,
            payroll: $this->payroll,
        );
    }

    /**
     * @param AttendanceCalendar[] $calendars
     * @return array<string, HolidayType>
     */
    private function indexHolidaysByDate(array $calendars, int $branchId): array
    {
        $holidays = [];

        foreach ($calendars as $calendar) {
            if ($branchId <= 0) {
                continue;
            }

            $scopeBranchIds = $calendar->getScope()->getBranchIds();
            if (!in_array($branchId, $scopeBranchIds, true)) {
                continue;
            }

            $type = HolidayType::tryFrom($calendar->getHolidayType());
            if ($type === null) {
                continue;
            }

            $holidays[$calendar->getDate()] = $type;
        }

        return $holidays;
    }

    private function finalize(): self
    {
        $hek = $this->hek;
        $attendanceSummary = $this->attendance;
        $payroll = $this->payroll;

        $effectiveWorkDays = $hek['effective_work_days'];
        $presentDays = round(
            $attendanceSummary['full_work_days'] + ($attendanceSummary['half_work_days'] * 0.5),
            2
        );

        $attendanceSummary['present_days'] = $presentDays;
        $attendanceSummary['absent_days'] = round(
            max(0, $effectiveWorkDays - $presentDays),
            2
        );

        if ($presentDays >= $effectiveWorkDays) {
            $payroll['excess_days'] = round(
                $presentDays - $effectiveWorkDays,
                2
            );
        } else {
            $payroll['deficit_days'] = round(
                $effectiveWorkDays - $presentDays,
                2
            );
            $payroll['deduction_days'] = $payroll['deficit_days'];
        }

        return new self(
            hek: $hek,
            attendance: $attendanceSummary,
            payroll: $payroll,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findEmployeeItem(Attendance $attendance, int $employeeId): ?array
    {
        foreach ($attendance->getAttendance() as $item) {
            if ((int) ($item['employee_id'] ?? 0) === $employeeId) {
                return $item;
            }
        }

        return null;
    }
}
