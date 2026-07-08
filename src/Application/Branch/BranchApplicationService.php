<?php

declare(strict_types=1);

namespace App\Application\Branch;

// Application Layer
use App\Application\Branch\Command\CreateBranchCommand;
use App\Application\Branch\Command\UpdateBranchCommand;
use App\Application\Branch\Dto\BranchResponse;
use App\Application\Shared\Factory\DetailInfoFactory;
use App\Application\Branch\BranchDetailInfoFactory;

// Domain Layer
use App\Domain\Branch\Entity\Branch;
use App\Domain\Branch\Repository\BranchRepositoryInterface;
use App\Domain\Branch\Service\BranchDomainService;
use App\Domain\AttendancePolicies\Repository\AttendancePoliciesRepositoryInterface;
use App\Domain\WorkPattern\Repository\WorkPatternRepositoryInterface;
use App\Domain\Shared\Enums\BranchType;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Branch Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class BranchApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private BranchDetailInfoFactory $branchInfoFactory,
        private BranchRepositoryInterface $repository,
        private BranchDomainService $domainService,
        private AttendancePoliciesRepositoryInterface $attendancePoliciesRepository,
        private WorkPatternRepositoryInterface $workPatternRepository
    ) {
    }

    public function getResource(): string
    {
        return Branch::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): Branch
    {
        $data = $this->repository->findById(
            id: $id,
            status: $status
        );

        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }
        
        return $data;
    }
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(
            criteria: $criteria
        );
    }

    public function view(int $id): BranchResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return BranchResponse::fromEntity(
            entity: $data
        );
    }

    /**
     * TODO:
     * [ ] Move to Domain Service
     */

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

    public function create(CreateBranchCommand $command): BranchResponse
    {
        $attendancePolicies = $this->buildAttendancePoliciesInfo(
            attendancePoliciesId: $command->attendance_policies_id
        );

        $workPattern = $this->buildWorkPatternInfo(
            workPatternId: $command->work_pattern_id
        );

        $branchInfo = $this->branchInfoFactory
            ->buildFromPrimitives(
                branch_type: $command->branch_type,
                attendance_policies: $attendancePolicies,
                work_pattern: $workPattern
            );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $branchInfo->toArray()
            )
            ->build();

        $data = Branch::create(
            name: $command->name,
            code: $command->code,
            address: $command->address,
            attendancePoliciesId: $command->attendance_policies_id,
            workPatternId: $command->work_pattern_id,
            branchType: $command->branch_type,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return BranchResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateBranchCommand $command): BranchResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null
        );

        $newStatus = ResourceStatus::tryFrom($command->status);

        $hasFieldChanges = $data->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus
        );

        $data->updateName(
            newName: $command->name
        );

        if ($command->code !== null) {
            $data->updateCode(
                newCode: $command->code
            );
        }

        if ($command->address !== null) {
            $data->updateAddress(
                newAddress: $command->address
            );
        }

        $data->applyStatus(
            newStatus: $newStatus
        );

        if ($command->attendance_policies_id !== null) {
            $data->updateAttendancePoliciesId(
                attendancePoliciesId: $command->attendance_policies_id
            );
        }

        if ($command->work_pattern_id !== null) {
            $data->updateWorkPatternId(
                workPatternId: $command->work_pattern_id
            );
        }

        if ($command->branch_type !== null || $command->attendance_policies_id !== null || $command->work_pattern_id !== null) {
            $data->updateType(
                newType: $command->branch_type
            );

            $attendancePolicies = $this->buildAttendancePoliciesInfo(
                attendancePoliciesId: $command->attendance_policies_id ?? $data->getAttendancePoliciesId()
            );

            $workPattern = $this->buildWorkPatternInfo(
                workPatternId: $command->work_pattern_id ?? $data->getWorkPatternId()
            );

            $branchInfo = $this->branchInfoFactory
                ->buildFromPrimitives(
                    branch_type: $command->branch_type ?? $data->getType(),
                    attendance_policies: $attendancePolicies,
                    work_pattern: $workPattern
                );

            $detailInfo = $this->detailInfoFactory
                ->update(
                    detailInfo: $data->getDetailInfo(),
                    payload: $branchInfo->toArray(),
                )
                ->build();

            $data->updateDetailInfo(
                detailInfo: $detailInfo
            );
        }

        return BranchResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): BranchResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data, 
            version: $lockVersion,
        );

        $this->domainService->guardPermission(
            id: $id,
            authorizer: $this->auth,
            permission: 'branch.delete',
            resource: $this->getResource(),
        );
        
        $this->domainService->ensureDeletable(
            entity: $data,
            resource: $this->getResource(),
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return BranchResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): BranchResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: ResourceStatus::deleted()->value()
        );
        
        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: false,
            newStatus: ResourceStatus::restored()
        );

        $data->markAsRestored();

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        $restoredData = $this->repository->restore(
            id: $data->getId()
        );
        
        return BranchResponse::fromEntity(
            entity: $restoredData
        );
    }
}
