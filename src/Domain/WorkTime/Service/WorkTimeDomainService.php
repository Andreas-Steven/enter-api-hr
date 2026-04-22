<?php

declare(strict_types=1);

namespace App\Domain\WorkTime\Service;

// Domain Layer
use App\Domain\WorkTime\Entity\WorkTime;
use App\Domain\WorkTime\Repository\WorkTimeRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * WorkTime Domain Service
 * 
 * Pure business logic and domain rules
 */
final class WorkTimeDomainService
{
    use DomainValidator;
    
    public function __construct(
        private WorkTimeRepositoryInterface $repository
    ) {}

    /**
     * Place WorkTime-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     * 
     * WorkTimes:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */ 
}
