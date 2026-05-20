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
        ?int $branchId = null,
    ): BranchDetailInfo {
        $branchType = BranchType::from($branchId);
        if ($branchType === null) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'branch.invalid_branch_id', 
                    params: [
                        'branch_id' => $branchId,
                        'must_be' => implode(', ', array_map(fn($case) => $case->value, BranchType::cases()))
                    ]
                )
            );
        }

        return new BranchDetailInfo(
            branch: [
                'id' => $branchType->value,
                'name' => $branchType->getLabel(),
            ],
        );
    }
}
