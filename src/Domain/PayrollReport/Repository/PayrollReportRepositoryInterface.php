<?php

declare(strict_types=1);

namespace App\Domain\PayrollReport\Repository;

// Domain Layer
use App\Domain\PayrollReport\Entity\PayrollReport;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * PayrollReport Repository Interface
 * 
 * Pure repository pattern for PayrollReport aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface PayrollReportRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?PayrollReport;

    public function findByName(string $name, ?int $status = null): ?PayrollReport;

    public function existsByName(string $name, ?int $status = null): bool;

    /**
     * Find a PayrollReport linked to a specific AttendanceSummary.
     *
     * Used by the review cascade recalc to find and update the
     * PayrollReport associated with a resolved review's source summary.
     */
    public function findByAttendanceSummaryId(int $attendanceSummaryId, ?int $status = null): ?PayrollReport;

    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(PayrollReport $payrollReport): PayrollReport;

    public function update(PayrollReport $payrollReport): PayrollReport;

    public function delete(PayrollReport $payrollReport): PayrollReport;

    public function restore(int $id): ?PayrollReport;

}
