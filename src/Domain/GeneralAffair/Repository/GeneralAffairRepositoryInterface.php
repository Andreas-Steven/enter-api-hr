<?php

declare(strict_types=1);

namespace App\Domain\GeneralAffair\Repository;

// Domain Layer
use App\Domain\GeneralAffair\Entity\GeneralAffair;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * GeneralAffair Repository Interface
 * 
 * Pure repository pattern for GeneralAffair aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface GeneralAffairRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?GeneralAffair;

    /* public function findByName(string $name, ?int $status = null): ?GeneralAffair;

    public function existsByName(string $name, ?int $status = null): bool; */

    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(GeneralAffair $generalAffair): GeneralAffair;

    public function update(GeneralAffair $generalAffair): GeneralAffair;

    public function delete(GeneralAffair $generalAffair): GeneralAffair;

    public function restore(int $id): ?GeneralAffair;
    
    public function findByUniqueCriteria(int $productId, int $categoryId, int $locationId, ?string $assetCode = null): ?GeneralAffair;
}