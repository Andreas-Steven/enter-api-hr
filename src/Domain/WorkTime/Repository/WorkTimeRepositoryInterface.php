<?php

declare(strict_types=1);

namespace App\Domain\WorkTime\Repository;

// Domain Layer
use App\Domain\WorkTime\Entity\WorkTime;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * WorkTime Repository Interface
 * 
 * Pure repository pattern for WorkTime aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface WorkTimeRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?WorkTime;

    public function findByName(string $name, ?int $status = null): ?WorkTime;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(WorkTime $entity): WorkTime;

    public function update(WorkTime $entity): WorkTime;

    public function delete(WorkTime $entity): WorkTime;

    public function restore(int $id): ?WorkTime;

}
