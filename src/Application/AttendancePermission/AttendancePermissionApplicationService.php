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
use App\Domain\AttendancePermission\ValueObject\AttendancePeriod;
use App\Domain\EmployeeLeave\Entity\EmployeeLeave;
use App\Domain\EmployeeLeave\Repository\EmployeeLeaveRepositoryInterface;
use App\Domain\Shared\Enums\PermissionType;
use App\Domain\Shared\Enums\LeaveType;
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
        private AttendancePermissionDetailInfoFactory $permissionInfoFactory,
        private EmployeeLeaveRepositoryInterface $employeeLeaveRepository,
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
            startDate: $command->startDate,
            endDate: $command->endDate
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
            startDate: $command->startDate,
            endDate: $command->endDate,
            period: AttendancePeriod::fromDateString($command->startDate)->toInt(),
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
            startDate: $command->startDate ?? $data->getStartDate(),
            endDate: $command->endDate ?? $data->getEndDate()
        );

        if (!empty($command->note)) {
           $data->updateNote(
                note: $command->note
            );
        }

        if (!empty($command->employeeId)) {
            $data->updateEmployeeId($command->employeeId);
        }

        if (!empty($command->permissionType)) {
            $data->updatePermissionType(
                permissionType: $command->permissionType
            );
        }

        if (!empty($command->startDate)) {
            $data->updateStartDate(
                startDate: $command->startDate
            );
        }

        if (!empty($command->endDate)) {
            $data->updateEndDate(
                endDate: $command->endDate
            );
        }

        if (!empty($command->revision)) {
            $data->updateRevision(
                newRevision: Revision::fromArray($command->revision)
            );
        }
                
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

        // Sync leave quota BEFORE persisting the permission so the quota
        // guard (insufficient remaining) can abort the approval without
        // leaving a half-approved permission behind. The EmployeeLeave
        // entity is mutated in memory here and persisted after the
        // permission, so a quota failure rolls back cleanly.
        $leaveToDebit = $this->syncLeaveRecordOnApprove(
            permission: $data,
            note: $command->approvedNote,
        );

        $updated = $this->repository->update(
            entity: $data
        );

        if ($leaveToDebit !== null) {
            $this->employeeLeaveRepository->update(entity: $leaveToDebit);
        }

        return AttendancePermissionResponse::fromEntity(
            entity: $updated
        );
    }

    public function reject(int $id, RejectAttendancePermissionCommand $command): AttendancePermissionResponse
    {
        $data = $this->getEntityById(id: $id, status: null);

        $this->domainService->guardApprovalRequiresActiveStatus(
            entity: $data
        );

        $newStatus = ResourceStatus::completed();

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null
        );

        // Capture previous approval state BEFORE applying rejection. Only
        // permissions that were previously APPROVED have a leave_record that
        // needs to be reversed; rejecting a PENDING permission leaves no
        // leave footprint to undo.
        $wasApproved = $data->getApproved() === ApprovalStatus::APPROVED->value;

        $leaveToReverse = $wasApproved
            ? $this->syncLeaveRecordOnReject(
                permission: $data,
                note: $command->approvedNote,
            )
            : null;

        $data->applyApproval(
            newStatus: ApprovalStatus::REJECTED,
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
            ->withRejected()
            ->build();

        $data->updateDetailInfo(detailInfo: $detailInfo);

        $updated = $this->repository->update(entity: $data);

        if ($leaveToReverse !== null) {
            $this->employeeLeaveRepository->update(entity: $leaveToReverse);
        }

        return AttendancePermissionResponse::fromEntity(
            entity: $updated
        );
    }

    /**
     * Resolves the EmployeeLeave quota record that should be debited for the
     * given permission, or null when the permission type has no leave quota
     * mapping or no matching active EmployeeLeave exists.
     *
     * Mapping (PermissionType -> LeaveType):
     *  - ANNUAL_LEAVE -> ANNUAL
     *
     * Other permission types (SICK, LEAVE_EARLY, LATE, PERMISSION_LEAVE,
     * UNPAID_LEAVE) do not consume a tracked quota here and are skipped.
     * Add new mappings to {@see mapPermissionTypeToLeaveType()} to extend.
     */
    private function resolveLeaveForPermission(AttendancePermission $permission): ?EmployeeLeave
    {
        $leaveType = $this->mapPermissionTypeToLeaveType($permission->getPermission());
        if ($leaveType === null) {
            return null;
        }

        $quotas = $this->employeeLeaveRepository->findByEmployeeAndPeriod(
            employeeId: $permission->getEmployeeId(),
            period: $permission->getPeriod(),
        );

        foreach ($quotas as $quota) {
            if ($quota->getLeaveType() === $leaveType->value && $quota->getStatus()->isActive()) {
                return $quota;
            }
        }

        return null;
    }

    private function mapPermissionTypeToLeaveType(PermissionType $permissionType): ?LeaveType
    {
        return match ($permissionType) {
            PermissionType::ANNUAL_LEAVE => LeaveType::ANNUAL,
            default => null,
        };
    }

    /**
     * Computes the total day count covered by the permission (inclusive of
     * start and end dates), mirroring AttendancePermissionDetailInfoFactory.
     */
    private function computeTotalDay(AttendancePermission $permission): int
    {
        $start = $permission->getStartDate();
        $end = $permission->getEndDate();

        if ($start === null) {
            return 1;
        }
        if ($end === null) {
            return 1;
        }

        $startDt = new \DateTime($start);
        $endDt = new \DateTime($end);
        $diff = $startDt->diff($endDt);

        return $diff->days + 1;
    }

    /**
     * Appends an "approved" leave_record to the matching EmployeeLeave and
     * returns the mutated entity for the caller to persist. Returns null
     * when there is no matching quota to debit.
     *
     * The quota guard runs inside EmployeeLeave::addLeaveRecord() and throws
     * a ConflictException when remaining < total_day, which aborts the
     * approval flow.
     */
    private function syncLeaveRecordOnApprove(AttendancePermission $permission, ?string $note): ?EmployeeLeave
    {
        $leave = $this->resolveLeaveForPermission($permission);
        if ($leave === null) {
            return null;
        }

        $leave->addLeaveRecord(
            permissionId: (int) $permission->getId(),
            startDate: $permission->getStartDate() ?? '',
            endDate: $permission->getEndDate(),
            totalDay: $this->computeTotalDay($permission),
            status: 'approved',
            note: $note,
        );

        return $leave;
    }

    /**
     * Appends a "rejected" leave_record to the matching EmployeeLeave to
     * reverse a previously-approved consumption, and returns the mutated
     * entity for the caller to persist. Returns null when there is no
     * matching quota to reverse.
     *
     * used_amount is recomputed as sum(approved) - sum(rejected), so the
     * rejected record restores the quota without erasing the audit trail.
     */
    private function syncLeaveRecordOnReject(AttendancePermission $permission, ?string $note): ?EmployeeLeave
    {
        $leave = $this->resolveLeaveForPermission($permission);
        if ($leave === null) {
            return null;
        }

        $leave->addLeaveRecord(
            permissionId: (int) $permission->getId(),
            startDate: $permission->getStartDate() ?? '',
            endDate: $permission->getEndDate(),
            totalDay: $this->computeTotalDay($permission),
            status: 'rejected',
            note: $note,
        );

        return $leave;
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(
            criteria: $criteria
        );
    }

    /**
     * Attendance Permission Report System
     */
    public function reportData(int $employeeId, int $period): array
    {
        $criteria = new SearchCriteria(
            filter: [
                'employee_id' => $employeeId,
            ],
            page: 1,
            pageSize: 200,
            sortBy: 'start_date',
            sortDir: 'asc',
            allowedSort: [
                'start_date' => 'start_date',
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