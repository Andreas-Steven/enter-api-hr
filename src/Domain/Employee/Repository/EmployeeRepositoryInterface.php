<?php

declare(strict_types=1);

namespace App\Domain\Employee\Repository;

// Domain Layer
use App\Domain\Employee\Entity\Employee;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * Employee Repository Interface
 * 
 * Pure repository pattern for Employee aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface EmployeeRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?Employee;

    public function findByName(string $name, ?int $status = null): ?Employee;

    /**
     * @return array<int, Employee>
     */
    public function findAllActive(): array;

    /**
     * @return array<int, Employee>
     */
    public function findByBranchId(int $branchId): array;

    /**
     * @return array<int, Employee>
     */
    public function findByDivisionId(int $divisionId): array;

    /**
     * @return array<int, Employee>
     */
    public function findByEmployeeTypeId(int $employeeTypeId): array;

    /**
     * @param array<int, int> $ids
     * @return array<int, Employee>
     */
    public function findByIds(array $ids): array;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(Employee $employee): Employee;

    public function update(Employee $employee): Employee;

    public function delete(Employee $employee): Employee;

    public function restore(int $id): ?Employee;

}
