<?php

declare(strict_types=1);

namespace App\Domain\GeneralAffairCategory\Repository;

// Domain Layer
use App\Domain\GeneralAffairCategory\Entity\GeneralAffairCategory;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * GeneralAffairCategory Repository Interface
 * 
 * Pure repository pattern for GeneralAffairCategory aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface GeneralAffairCategoryRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?GeneralAffairCategory;

    public function findByName(string $name, ?int $status = null): ?GeneralAffairCategory;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(GeneralAffairCategory $generalAffairCategory): GeneralAffairCategory;

    public function update(GeneralAffairCategory $generalAffairCategory): GeneralAffairCategory;

    public function delete(GeneralAffairCategory $generalAffairCategory): GeneralAffairCategory;

    public function restore(int $id): ?GeneralAffairCategory;

}
