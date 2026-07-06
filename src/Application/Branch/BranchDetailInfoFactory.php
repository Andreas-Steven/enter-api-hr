<?php

declare(strict_types=1);

namespace App\Application\Branch;

use App\Application\Branch\Dto\BranchDetailInfo;

use App\Domain\Shared\Enums\BranchType;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Attendance\ValueObject\AttendanceRecords;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;

use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\NotFoundException;

final readonly class BranchDetailInfoFactory
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private AttendanceRepositoryInterface $attendanceRepository,
    ) {}

    public function buildFromPrimitives(
        ?int $branch_type = null,
        ?array $attendance_policies = null,
        ?array $work_pattern = null,
    ): BranchDetailInfo {
        if ($branch_type === null) {
            return new BranchDetailInfo(
                branch_type: null,
                attendance_policies: $attendance_policies,
                work_pattern: $work_pattern,
            );
        }

        try {
            $branchType = BranchType::from($branch_type);
        } catch (\ValueError $e) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'branch.invalid_branch_id', 
                    params: [
                        'branch_id' => $branch_type,
                        'must_be' => implode(', ', array_map(fn($case) => $case->value, BranchType::cases()))
                    ]
                )
            );
        }

        return new BranchDetailInfo(
            branch_type: [
                'id' => $branchType->value,
                'name' => $branchType->getLabel(),
            ],
            attendance_policies: $attendance_policies,
            work_pattern: $work_pattern,
        );
    }
}
