<?php

declare(strict_types=1);

namespace App\Application\Branch;

use App\Application\Branch\Dto\BranchDetailInfo;

use App\Domain\Shared\Enums\BranchType;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Domain\Attendance\ValueObject\AttendanceRecords;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Domain\AttendancePolicies\Repository\AttendancePoliciesRepositoryInterface;
use App\Domain\WorkPattern\Repository\WorkPatternRepositoryInterface;

use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\NotFoundException;

final readonly class BranchDetailInfoFactory
{
    public function __construct(
        private EmployeeRepositoryInterface $employeeRepository,
        private AttendanceRepositoryInterface $attendanceRepository,
        private AttendancePoliciesRepositoryInterface $attendancePoliciesRepository,
        private WorkPatternRepositoryInterface $workPatternRepository,
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

    public function buildFromIds(
        ?int $branch_type = null,
        ?int $attendance_policies_id = null,
        ?int $work_pattern_id = null,
    ): BranchDetailInfo {
        $attendancePolicies = $attendance_policies_id !== null
            ? $this->buildAttendancePoliciesInfo($attendance_policies_id)
            : null;

        $workPattern = $work_pattern_id !== null
            ? $this->buildWorkPatternInfo($work_pattern_id)
            : null;

        return $this->buildFromPrimitives(
            branch_type: $branch_type,
            attendance_policies: $attendancePolicies,
            work_pattern: $workPattern,
        );
    }

    private function buildAttendancePoliciesInfo(?int $attendancePoliciesId): ?array
    {
        if ($attendancePoliciesId === null) {
            return null;
        }

        $attendancePolicies = $this->attendancePoliciesRepository->findById(
            id: $attendancePoliciesId,
            status: null
        );

        if ($attendancePolicies === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'AttendancePolicies',
                        'field' => 'id',
                        'value' => $attendancePoliciesId
                    ]
                )
            );
        }

        return [
            'id' => $attendancePolicies->getId(),
            'name' => $attendancePolicies->getName(),
            'policy_detail' => $attendancePolicies->getPolicyDetail()->toArray(),
        ];
    }

    private function buildWorkPatternInfo(?int $workPatternId): ?array
    {
        if ($workPatternId === null) {
            return null;
        }

        $workPattern = $this->workPatternRepository->findById(
            id: $workPatternId,
            status: null
        );

        if ($workPattern === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: [
                        'resource' => 'WorkPattern',
                        'field' => 'id',
                        'value' => $workPatternId
                    ]
                )
            );
        }

        return [
            'id' => $workPattern->getId(),
            'name' => $workPattern->getName(),
            'working_days' => $workPattern->getWorkingDays()->toArray(),
        ];
    }
}
