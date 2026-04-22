<?php

declare(strict_types=1);

namespace App\Domain\EmployeeType\Repository;

// Domain Layer
use App\Domain\EmployeeType\Entity\EmployeeType;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * EmployeeType Repository Interface
 * 
 * Pure repository pattern for EmployeeType aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface EmployeeTypeRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?EmployeeType;

    public function findByName(string $name, ?int $status = null): ?EmployeeType;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(EmployeeType $employeeType): EmployeeType;

    public function update(EmployeeType $employeeType): EmployeeType;

    public function delete(EmployeeType $employeeType): EmployeeType;

    public function restore(int $id): ?EmployeeType;

}
