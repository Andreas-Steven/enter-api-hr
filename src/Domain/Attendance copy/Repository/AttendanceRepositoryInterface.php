<?php

declare(strict_types=1);

namespace App\Domain\Attendance\Repository;

// Domain Layer
use App\Domain\Attendance\Entity\Attendance;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * Attendance Repository Interface
 * 
 * Pure repository pattern for Attendance aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AttendanceRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?Attendance;

    public function findByName(string $name, ?int $status = null): ?Attendance;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(Attendance $attendance): Attendance;

    public function update(Attendance $attendance): Attendance;

    public function delete(Attendance $attendance): Attendance;

    public function restore(int $id): ?Attendance;

    public function findByDate(string $date, ?int $status = null): ?Attendance;
}
