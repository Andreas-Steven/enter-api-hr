<?php

declare(strict_types=1);

namespace App\Application\Branch;

use App\Application\Branch\Dto\BranchDetailInfo;

use App\Domain\Shared\Enums\BranchType;
use App\Domain\AttendancePolicies\Repository\AttendancePoliciesRepositoryInterface;
use App\Domain\WorkPattern\Repository\WorkPatternRepositoryInterface;

use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\NotFoundException;

final readonly class BranchDetailInfoFactory
{
    public function __construct(
        private AttendancePoliciesRepositoryInterface $attendancePoliciesRepository,
        private WorkPatternRepositoryInterface $workPatternRepository,
    ) {}

    public function buildFromPrimitives(
        ?int $branch_type = null,
        ?array $attendance_policies = null,
        ?array $work_pattern = null,
    ): BranchDetailInfo {
        return new BranchDetailInfo(
            branch_type: $this->buildBranchType($branch_type),
            attendance_policies: $attendance_policies,
            work_pattern: $work_pattern,
        );
    }

    public function buildFromIds(
        ?int $branch_type = null,
        ?int $attendance_policies_id = null,
        ?int $work_pattern_id = null,
    ): BranchDetailInfo {
        $attendancePolicies = null;
        if ($attendance_policies_id !== null) {
            $attendancePolicies = $this->attendancePoliciesRepository->findById(
                id: $attendance_policies_id,
                status: null
            );

            if ($attendancePolicies === null) {
                throw new NotFoundException(
                    translate: Message::create(
                        key: 'resource.not_found',
                        params: [
                            'resource' => 'AttendancePolicies',
                            'field' => 'id',
                            'value' => $attendance_policies_id
                        ]
                    )
                );
            }
        }

        $workPattern = null;
        if ($work_pattern_id !== null) {
            $workPattern = $this->workPatternRepository->findById(
                id: $work_pattern_id,
                status: null
            );

            if ($workPattern === null) {
                throw new NotFoundException(
                    translate: Message::create(
                        key: 'resource.not_found',
                        params: [
                            'resource' => 'WorkPattern',
                            'field' => 'id',
                            'value' => $work_pattern_id
                        ]
                    )
                );
            }
        }

        return new BranchDetailInfo(
            branch_type: $this->buildBranchType($branch_type),
            attendance_policies: $attendancePolicies?->toPersistence(),
            work_pattern: $workPattern?->toPersistence(),
        );
    }

    private function buildBranchType(?int $branch_type): ?array
    {
        if ($branch_type === null) {
            return null;
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

        return [
            'id' => $branchType->value,
            'name' => $branchType->getLabel(),
        ];
    }
}
