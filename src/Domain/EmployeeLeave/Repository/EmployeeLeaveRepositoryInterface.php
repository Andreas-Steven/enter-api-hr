<?php

declare(strict_types=1);

namespace App\Domain\EmployeeLeave\Repository;

// Domain Layer
use App\Domain\EmployeeLeave\Entity\EmployeeLeave;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * EmployeeLeave Repository Interface
 * 
 * Pure repository pattern for EmployeeLeave aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface EmployeeLeaveRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?EmployeeLeave;

    public function findByName(string $name, ?int $status = null): ?EmployeeLeave;

    public function existsByName(string $name, ?int $status = null): bool;

    /**
     * Returns leave quotas whose validity range overlaps the requested attendance period.
     *
     * @return array<int, EmployeeLeave>
     */
    public function findByEmployeeAndPeriod(int $employeeId, int $period, ?int $status = null): array;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(EmployeeLeave $employeeLeave): EmployeeLeave;

    public function update(EmployeeLeave $employeeLeave): EmployeeLeave;

    public function delete(EmployeeLeave $employeeLeave): EmployeeLeave;

    public function restore(int $id): ?EmployeeLeave;

}
