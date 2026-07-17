<?php

declare(strict_types=1);

namespace App\Domain\DeductionType\Repository;

// Domain Layer
use App\Domain\DeductionType\Entity\DeductionType;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * DeductionType Repository Interface
 * 
 * Pure repository pattern for DeductionType aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface DeductionTypeRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?DeductionType;

    public function findByName(string $name, ?int $status = null): ?DeductionType;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(DeductionType $deductionType): DeductionType;

    public function update(DeductionType $deductionType): DeductionType;

    public function delete(DeductionType $deductionType): DeductionType;

    public function restore(int $id): ?DeductionType;

}
