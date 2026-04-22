<?php

declare(strict_types=1);

namespace App\Domain\GeneralAffairLocation\Repository;

// Domain Layer
use App\Domain\GeneralAffairLocation\Entity\GeneralAffairLocation;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * GeneralAffairLocation Repository Interface
 * 
 * Pure repository pattern for GeneralAffairLocation aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface GeneralAffairLocationRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?GeneralAffairLocation;

    public function findByName(string $name, ?int $status = null): ?GeneralAffairLocation;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(GeneralAffairLocation $generalAffairLocation): GeneralAffairLocation;

    public function update(GeneralAffairLocation $generalAffairLocation): GeneralAffairLocation;

    public function delete(GeneralAffairLocation $generalAffairLocation): GeneralAffairLocation;

    public function restore(int $id): ?GeneralAffairLocation;

}
