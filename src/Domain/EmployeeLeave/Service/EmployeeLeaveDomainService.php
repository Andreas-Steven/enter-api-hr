<?php

declare(strict_types=1);

namespace App\Domain\EmployeeLeave\Service;

// Domain Layer
use App\Domain\EmployeeLeave\Entity\EmployeeLeave;
use App\Domain\EmployeeLeave\Repository\EmployeeLeaveRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * EmployeeLeave Domain Service
 * 
 * Pure business logic and domain rules
 */
final class EmployeeLeaveDomainService
{
    use DomainValidator;
    
    public function __construct(
        private EmployeeLeaveRepositoryInterface $repository
    ) {}

    /**
     * Place EmployeeLeave-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     * 
     * EmployeeLeaves:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */ 
}
