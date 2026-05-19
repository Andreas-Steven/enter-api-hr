<?php

declare(strict_types=1);

namespace App\Application\EmployeeLeave\Command;

final readonly class CreateEmployeeLeaveCommand
{
    public function __construct(
        public string $name,
        public string $leaveType,
        public int $employeeId,
        public string $periodStartDate,
        public string $periodEndDate,
        public int $totalAmount,
        public ?int $adjustmentAmount,
        public ?int $usedAmount,
        public array $leaveRecords,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        string $leaveType,
        int $employeeId,
        string $periodStartDate,
        string $periodEndDate,
        int $totalAmount,
        ?int $adjustmentAmount,
        ?int $usedAmount,
        array $leaveRecords,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(
            name: $name,
            leaveType: $leaveType,
            employeeId: $employeeId,
            periodStartDate: $periodStartDate,
            periodEndDate: $periodEndDate,
            totalAmount: $totalAmount,
            adjustmentAmount: $adjustmentAmount,
            usedAmount: $usedAmount,
            leaveRecords: $leaveRecords,
            status: $status,
            detailInfo: $detailInfo,
        );
    }
}