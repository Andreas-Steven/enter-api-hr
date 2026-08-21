<?php

declare(strict_types=1);

namespace App\Application\EmployeeLeave\Command;

/**
 * Command for the grand-assign use case: creates one EmployeeLeave per
 * active employee matching the scope criteria.
 *
 * Scope (first match wins):
 *  - employee_ids:     explicit list of employee IDs
 *  - branch_id:        all active employees in a branch
 *  - division_id:      all active employees in a division
 *  - employee_type_id: all active employees of a type
 *
 * Each employee receives a leave quota with an auto-generated name in the
 * format "{baseName} - {employeeName}". Employees that already have a leave
 * with the exact same generated name are skipped (idempotent).
 *
 * @param array{branch_id?: int|null, division_id?: int|null, employee_type_id?: int|null, employee_ids?: array<int, int>|null} $scope
 */
final readonly class AssignEmployeeLeaveCommand
{
    public function __construct(
        public string $name,
        public string $leaveType,
        public string $periodStartDate,
        public string $periodEndDate,
        public int $initialAmount,
        public ?int $usedAmount,
        public array $leaveRecords,
        public int $status,
        public array $scope,
        public ?array $detailInfo = null,
    ) {}

    /**
     * adjustmentRecords always starts empty on grand-assign; adjustments are
     * only ever appended via PUT /v1/employee-leave/{id}.
     */

    /**
     * @param array{branch_id?: int|null, division_id?: int|null, employee_type_id?: int|null, employee_ids?: array<int, int>|null} $scope
     */
    public static function create(
        string $name,
        string $leaveType,
        string $periodStartDate,
        string $periodEndDate,
        int $initialAmount,
        ?int $usedAmount,
        array $leaveRecords,
        int $status,
        array $scope,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            leaveType: $leaveType,
            periodStartDate: $periodStartDate,
            periodEndDate: $periodEndDate,
            initialAmount: $initialAmount,
            usedAmount: $usedAmount,
            leaveRecords: $leaveRecords,
            status: $status,
            scope: $scope,
            detailInfo: $detailInfo,
        );
    }
}
