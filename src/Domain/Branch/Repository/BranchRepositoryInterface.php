<?php

declare(strict_types=1);

namespace App\Domain\Branch\Repository;

// Domain Layer
use App\Domain\Branch\Entity\Branch;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * Branch Repository Interface
 * 
 * Pure repository pattern for Branch aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface BranchRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?Branch;

    public function findByName(string $name, ?int $status = null): ?Branch;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(Branch $branch): Branch;

    public function update(Branch $branch): Branch;

    public function delete(Branch $branch): Branch;

    public function restore(int $id): ?Branch;

    public function findByIds(array $ids): array;

    public function existsById(int $id): bool;
}
