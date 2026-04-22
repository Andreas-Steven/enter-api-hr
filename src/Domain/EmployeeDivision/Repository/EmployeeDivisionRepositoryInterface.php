<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDivision\Repository;

// Domain Layer
use App\Domain\EmployeeDivision\Entity\EmployeeDivision;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * EmployeeDivision Repository Interface
 * 
 * Pure repository pattern for EmployeeDivision aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface EmployeeDivisionRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?EmployeeDivision;

    public function findByName(string $name, ?int $status = null): ?EmployeeDivision;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(EmployeeDivision $employeeDivision): EmployeeDivision;

    public function update(EmployeeDivision $employeeDivision): EmployeeDivision;

    public function delete(EmployeeDivision $employeeDivision): EmployeeDivision;

    public function restore(int $id): ?EmployeeDivision;

}
