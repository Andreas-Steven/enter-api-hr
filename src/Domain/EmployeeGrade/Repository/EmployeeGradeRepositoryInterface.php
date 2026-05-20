<?php

declare(strict_types=1);

namespace App\Domain\EmployeeGrade\Repository;

// Domain Layer
use App\Domain\EmployeeGrade\Entity\EmployeeGrade;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * EmployeeGrade Repository Interface
 * 
 * Pure repository pattern for EmployeeGrade aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface EmployeeGradeRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?EmployeeGrade;

    public function findByName(string $name, ?int $status = null): ?EmployeeGrade;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(EmployeeGrade $employeeGrade): EmployeeGrade;

    public function update(EmployeeGrade $employeeGrade): EmployeeGrade;

    public function delete(EmployeeGrade $employeeGrade): EmployeeGrade;

    public function restore(int $id): ?EmployeeGrade;

}
