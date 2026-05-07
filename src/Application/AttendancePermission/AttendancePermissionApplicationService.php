<?php

declare(strict_types=1);

namespace App\Application\AttendancePermission;

// Application Layer
use App\Application\AttendancePermission\Command\CreateAttendancePermissionCommand;
use App\Application\AttendancePermission\Command\UpdateAttendancePermissionCommand;
use App\Application\AttendancePermission\Command\ApproveAttendancePermissionCommand;
use App\Application\AttendancePermission\Command\RejectAttendancePermissionCommand;
use App\Application\AttendancePermission\Dto\AttendancePermissionResponse;
use App\Application\AttendancePermission\AttendancePermissionDetailInfoFactory;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AttendancePermission\Entity\AttendancePermission;
use App\Domain\AttendancePermission\Repository\AttendancePermissionRepositoryInterface;
use App\Domain\AttendancePermission\Service\AttendancePermissionDomainService;
use App\Domain\AttendancePermission\ValueObject\Revision;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Domain\Shared\Enums\ApprovalStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AttendancePermission Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class AttendancePermissionApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AttendancePermissionRepositoryInterface $repository,
        private AttendancePermissionDomainService $domainService,
        private AttendancePermissionDetailInfoFactory $permissionInfoFactory
    ) {
    }

    public function getResource(): string
    {
        return AttendancePermission::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): AttendancePermission
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

    public function view(int $id): AttendancePermissionResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return AttendancePermissionResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateAttendancePermissionCommand $command): AttendancePermissionResponse
    {
        $permissionInfo = $this->permissionInfoFactory->buildFromPrimitives(
            employeeId: $command->employeeId,
            permissionId: $command->permissionType,
            date: $command->date
        );
        
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $permissionInfo->toArray()
            )
            ->withEmptyApproval()
            ->withEmptyRejection()
            ->build();
            
        $data = AttendancePermission::create(
            employeeId: $command->employeeId,
            permissionType: (int) $command->permissionType,
            date: $command->date,
            note: $command->note,
            approved: null,
            approvedNote: null,
            revision: Revision::fromArray($command->revision),
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        $this->domainService->ensureRevisionConsistency($data, $data->getRevision());

        return AttendancePermissionResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateAttendancePermissionCommand $command): AttendancePermissionResponse
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

        $data->applyStatus(
            newStatus: $newStatus
        );

        $permissionInfo = $this->permissionInfoFactory->buildFromPrimitives(
            employeeId: $command->employeeId ?? $data->getEmployeeId(),
            permissionId: $command->permissionType ?? $data->getPermissionType(),
            date: $command->date ?? $data->getDate()
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $permissionInfo->toArray(),
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        $this->domainService->ensureRevisionConsistency($data, $data->getRevision());

        return AttendancePermissionResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): AttendancePermissionResponse
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
            permission: 'attendance-permission.delete',
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

        return AttendancePermissionResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): AttendancePermissionResponse
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
        
        return AttendancePermissionResponse::fromEntity(
            entity: $restoredData
        );
    }
    
    /**
     * Approve and Reject
     */
    public function approve(int $id, ApproveAttendancePermissionCommand $command): AttendancePermissionResponse
    {
        $data = $this->getEntityById(
            id: $id, 
            status: null
        );

        $this->domainService->guardApprovalRequiresActiveStatus(
            entity: $data
        );

        $newStatus = ResourceStatus::completed();

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null
        );

        $data->applyApproval(
            newStatus: ApprovalStatus::APPROVED,
            note: $command->approvedNote
        );

        $data->applyStatus(
            newStatus: $newStatus
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->withApproved()
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return AttendancePermissionResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function reject(int $id, RejectAttendancePermissionCommand $command): AttendancePermissionResponse
    {
        $data = $this->getEntityById(id: $id, status: null);

        $this->domainService->guardApprovalRequiresActiveStatus(
            entity: $data
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null
        );

        $data->applyApproval(
            newStatus: ApprovalStatus::REJECTED,
            note: $command->approvedNote
        );
        
        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->withRejected()
            ->build();

        $data->updateDetailInfo(detailInfo: $detailInfo);

        return AttendancePermissionResponse::fromEntity(
            entity: $this->repository->update(entity: $data)
        );
    }

    /**
     * Attendance Permission Report System
     */
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(
            criteria: $criteria
        );
    }

    public function reportData(int $employeeId, int $period): array
    {
        $criteria = new SearchCriteria(
            filter: [
                'employee_id' => $employeeId,
            ],
            page: 1,
            pageSize: 200,
            sortBy: 'date',
            sortDir: 'asc',
            allowedSort: [
                'date' => 'date',
                'id' => 'id',
            ]
        );

        $result = $this->list(criteria: $criteria);

        return array_values(array_filter(
            $result->data,
            static fn(array $row): bool => (int) ($row['period'] ?? 0) === $period
        ));
    }
}