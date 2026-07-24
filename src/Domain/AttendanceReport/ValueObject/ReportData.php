<?php

declare(strict_types=1);

namespace App\Domain\AttendanceReport\ValueObject;

use App\Domain\Employee\Entity\Employee;

/**
 * Immutable carrier for generated attendance report data before it becomes
 * an AttendanceReport aggregate root.
 */
final readonly class ReportData
{
    public function __construct(
        private string $name,
        private int $period,
        private int $employeeId,
        private int $branchId,
        private int $divisionId,
        private int $positionId,
        private int $employeeTypeId,
        private int $workPatternId,
        private Summary $summary,
        private string $reportType,
        private Employee $employee,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function getEmployeeId(): int
    {
        return $this->employeeId;
    }

    public function getBranchId(): int
    {
        return $this->branchId;
    }

    public function getDivisionId(): int
    {
        return $this->divisionId;
    }

    public function getPositionId(): int
    {
        return $this->positionId;
    }

    public function getEmployeeTypeId(): int
    {
        return $this->employeeTypeId;
    }

    public function getWorkPatternId(): int
    {
        return $this->workPatternId;
    }

    public function getSummary(): Summary
    {
        return $this->summary;
    }

    public function getReportType(): string
    {
        return $this->reportType;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }
}
