<?php

declare(strict_types=1);

namespace App\Domain\AttendanceDevice\Repository;

// Domain Layer
use App\Domain\AttendanceDevice\Entity\AttendanceDevice;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * AttendanceDevice Repository Interface
 * 
 * Pure repository pattern for AttendanceDevice aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AttendanceDeviceRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?AttendanceDevice;

    public function findByName(string $name, ?int $status = null): ?AttendanceDevice;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(AttendanceDevice $entity): AttendanceDevice;

    public function update(AttendanceDevice $entity): AttendanceDevice;

    public function delete(AttendanceDevice $entity): AttendanceDevice;

    public function restore(int $id): ?AttendanceDevice;

    public function findAllByCodes(array $codes): array;

}
