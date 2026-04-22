<?php

declare(strict_types=1);

namespace App\Domain\EmployeePosition\Repository;

// Domain Layer
use App\Domain\EmployeePosition\Entity\EmployeePosition;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * EmployeePosition Repository Interface
 * 
 * Pure repository pattern for EmployeePosition aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface EmployeePositionRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?EmployeePosition;

    public function findByName(string $name, ?int $status = null): ?EmployeePosition;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(EmployeePosition $employeePosition): EmployeePosition;

    public function update(EmployeePosition $employeePosition): EmployeePosition;

    public function delete(EmployeePosition $employeePosition): EmployeePosition;

    public function restore(int $id): ?EmployeePosition;

}
