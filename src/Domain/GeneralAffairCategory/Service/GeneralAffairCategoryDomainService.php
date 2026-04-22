<?php

declare(strict_types=1);

namespace App\Domain\GeneralAffairCategory\Service;

// Domain Layer
use App\Domain\GeneralAffairCategory\Entity\GeneralAffairCategory;
use App\Domain\GeneralAffairCategory\Repository\GeneralAffairCategoryRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * GeneralAffairCategory Domain Service
 * 
 * Pure business logic and domain rules
 */
final class GeneralAffairCategoryDomainService
{
    use DomainValidator;
    
    public function __construct(
        private GeneralAffairCategoryRepositoryInterface $repository
    ) {}

    /**
     * Place GeneralAffairCategory-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     * 
     * GeneralAffairCategorys:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */ 
}
