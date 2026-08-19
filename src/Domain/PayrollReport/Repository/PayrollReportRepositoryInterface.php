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
     * @return list<PayrollReport>
     */
    public function findByPeriod(int $period, ?int $status = null): array;

    public function findByAttendanceSummaryId(int $attendanceSummaryId, ?int $status = null): ?PayrollReport;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function insert(PayrollReport $entity): PayrollReport;

    public function update(PayrollReport $entity): PayrollReport;

    public function delete(PayrollReport $entity): PayrollReport;

    public function restore(int $id): ?PayrollReport;
}
