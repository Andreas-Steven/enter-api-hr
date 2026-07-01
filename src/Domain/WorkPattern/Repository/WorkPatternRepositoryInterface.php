<?php

declare(strict_types=1);

namespace App\Domain\WorkPattern\Repository;

// Domain Layer
use App\Domain\WorkPattern\Entity\WorkPattern;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * WorkPattern Repository Interface
 *
 * Pure repository pattern for WorkPattern aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface WorkPatternRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?WorkPattern;

    public function findByName(string $name, ?int $status = null): ?WorkPattern;

    public function existsByName(string $name, ?int $status = null): bool;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function insert(WorkPattern $entity): WorkPattern;

    public function update(WorkPattern $entity): WorkPattern;

    public function delete(WorkPattern $entity): WorkPattern;

    public function restore(int $id): ?WorkPattern;

}
