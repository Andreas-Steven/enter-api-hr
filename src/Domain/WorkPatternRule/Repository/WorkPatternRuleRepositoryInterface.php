<?php

declare(strict_types=1);

namespace App\Domain\WorkPatternRule\Repository;

// Domain Layer
use App\Domain\WorkPatternRule\Entity\WorkPatternRule;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * WorkPatternRule Repository Interface
 * 
 * Pure repository pattern for WorkPatternRule aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface WorkPatternRuleRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?WorkPatternRule;

    public function findByName(string $name, ?int $status = null): ?WorkPatternRule;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(WorkPatternRule $workPatternRule): WorkPatternRule;

    public function update(WorkPatternRule $workPatternRule): WorkPatternRule;

    public function delete(WorkPatternRule $workPatternRule): WorkPatternRule;

    public function restore(int $id): ?WorkPatternRule;

}
