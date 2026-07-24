<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReport\ValueObject;

use App\Domain\Attendance\Entity\Attendance;
use App\Domain\Employee\Entity\Employee;
use App\Domain\Shared\Enums\AttendanceStatus;

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
                'full_work_days' => 0,
                'half_work_days' => 0,
                'effective_work_days' => 0.00,
            ],
            attendance: [
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
     */
    public static function fromEmployeeAndAttendance(
        Employee $employee,
        int $period,
        array $attendanceRecords,
    ): self {
        $summary = self::createDefault();

        foreach ($attendanceRecords as $attendance) {
            $summary = $summary->withAttendanceDay($employee, $attendance);
        }

        return $summary->finalize(count($attendanceRecords));
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

        $isDayOff = (bool) $attendance->getDetailInfo()->get('day_off', false);
        if ($isDayOff) {
            $hek['off_days']++;
        }

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
        $statusSummary = $info['summary'] ?? [];

        $isWorkHoliday = in_array(AttendanceStatus::WORK_HOLIDAY, $statusSummary, true);
        if ($isWorkHoliday) {
            $hek['work_holidays']++;
        }

        if (!$isDayOff) {
            if (!empty($detail['full_day'])) {
                $hek['full_work_days']++;
            } elseif (!empty($detail['half_day'])) {
                $hek['half_work_days']++;
            }
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

    private function finalize(int $totalDays): self
    {
        $hek = $this->hek;
        $attendanceSummary = $this->attendance;
        $payroll = $this->payroll;

        $hek['total_days'] = $totalDays;
        $hek['effective_work_days'] = round(
            $hek['full_work_days'] + ($hek['half_work_days'] * 0.5),
            2
        );

        $presentDays = round(
            $hek['full_work_days'] + ($hek['half_work_days'] * 0.5),
            2
        );
        $attendanceSummary['present_days'] = $presentDays;
        $attendanceSummary['absent_days'] = round(
            max(0, $hek['effective_work_days'] - $presentDays),
            2
        );

        if ($presentDays >= $hek['effective_work_days']) {
            $payroll['excess_days'] = round(
                $presentDays - $hek['effective_work_days'],
                2
            );
        } else {
            $payroll['deficit_days'] = round(
                $hek['effective_work_days'] - $presentDays,
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
