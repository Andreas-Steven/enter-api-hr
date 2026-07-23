<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebt\Repository;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * EmployeeDebt Repository Interface
 * 
 * Pure repository pattern for EmployeeDebt aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface EmployeeDebtRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?EmployeeDebt;

    public function findByName(string $name, ?int $status = null): ?EmployeeDebt;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(EmployeeDebt $employeeDebt): EmployeeDebt;

    public function update(EmployeeDebt $employeeDebt): EmployeeDebt;

    public function delete(EmployeeDebt $employeeDebt): EmployeeDebt;

    public function restore(int $id): ?EmployeeDebt;

}
