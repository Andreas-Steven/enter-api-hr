<?php

declare(strict_types=1);

namespace App\Domain\AttendanceFineRate\Repository;

// Domain Layer
use App\Domain\AttendanceFineRate\Entity\AttendanceFineRate;
use App\Domain\Shared\Enums\IncidentType;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * AttendanceFineRate Repository Interface
 *
 * Pure repository pattern for AttendanceFineRate aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AttendanceFineRateRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?AttendanceFineRate;

    public function findByIncidentType(IncidentType $incidentType, ?int $status = null): ?AttendanceFineRate;

    public function findByName(string $name, ?int $status = null): ?AttendanceFineRate;

    public function existsByName(string $name, ?int $status = null): bool;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function insert(AttendanceFineRate $attendanceFineRate): AttendanceFineRate;

    public function update(AttendanceFineRate $attendanceFineRate): AttendanceFineRate;

    public function delete(AttendanceFineRate $attendanceFineRate): AttendanceFineRate;

    public function restore(int $id): ?AttendanceFineRate;
}
