<?php

declare(strict_types=1);

namespace App\Application\EmployeeLeave\Command;

final readonly class UpdateEmployeeLeaveCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?string $leaveType,
        public ?int $employeeId,
        public ?string $periodStartDate,
        public ?string $periodEndDate,
        public ?int $initialAmount,
        public ?int $usedAmount,
        public ?array $leaveRecords,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        ?string $name = null,
        ?string $leaveType = null,
        ?int $employeeId = null,
        ?string $periodStartDate = null,
        ?string $periodEndDate = null,
        ?int $initialAmount = null,
        ?int $usedAmount = null,
        ?array $leaveRecords = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            leaveType: $leaveType,
            employeeId: $employeeId,
            periodStartDate: $periodStartDate,
            periodEndDate: $periodEndDate,
            initialAmount: $initialAmount,
            usedAmount: $usedAmount,
            leaveRecords: $leaveRecords,
            status: $status,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}
