<?php

declare(strict_types=1);

namespace App\Domain\AttendancePermission\Service;

// Domain Layer
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

use App\Domain\AttendancePermission\Repository\AttendancePermissionRepositoryInterface;
use App\Domain\AttendancePermission\Entity\AttendancePermission;
use App\Domain\AttendancePermission\ValueObject\Revision;
use App\Domain\Shared\Enums\PermissionType;
use App\Domain\Shared\Enums\ApprovalStatus;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ConflictException;

/**
 * AttendancePermission Domain Service
 * 
 * Pure business logic and domain rules
 */
final class AttendancePermissionDomainService
{
    use DomainValidator;
    
    public function __construct(
        private AttendancePermissionRepositoryInterface $repository
    ) {}

    /**
     * Place AttendancePermission-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     * 
     * AttendancePermissions:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */ 

    public function ensureRevisionConsistency(AttendancePermission $entity, ?Revision $revision): void
    {
        $type = $entity->getPermission(); 

        // 1. Jika tidak butuh revisi (SICK = 1, SICK_WITH_RECEIPT = 2, & ANNUAL_LEAVE = 5)
        if (!$type->requiresRevision()) {
            if ($revision !== null && !$revision->isEmpty()) {
                $entity->clearRevision();
            }

            return;
        }

        // 2. Jika tipenya LATE (LATE = 4)
        if ($type === PermissionType::LATE && ($revision === null || !$revision->hasStartTime())) {
             throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_permission.start_time_required',
                    params: ['type' => $type->getLabel()],
                    domain: 'app',
                )
            );
        }

            // 3. Jika tipenya LEAVE_EARLY (LEAVE_EARLY = 3)
        if ($type === PermissionType::LEAVE_EARLY && ($revision === null || !$revision->hasEndTime())) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_permission.end_time_required',
                    params: ['type' => $type->getLabel()],
                    domain: 'app',
                )
            );
        }
    }

    /**
     * Guard that approval operations require active status
     */
    public function guardApprovalRequiresActiveStatus(AttendancePermission $entity): void
    {
        if (!$entity->isActive()) {
            throw new ConflictException(
                translate: Message::create(
                    key: 'resource.update_not_allowed_by_status',
                    params: [
                        'resource' => $entity->getResource(),
                        'current_status' => $entity->getStatus()->label(),
                    ]
                )
            );
        }
        
        return;  
    }

    /**
     * Guard approval status transition
     */
    public function guardApprovalTransition(ApprovalStatus $currentStatus, ApprovalStatus $newStatus): void
    {
        if (!$newStatus->canTransitionFrom($currentStatus)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'attendance_permission.invalid_approval_transition',
                    params: [
                        'from' => $currentStatus->getLabel(),
                        'to' => $newStatus->getLabel(),
                    ],
                    domain: 'app',
                )
            );
        }
    }
}