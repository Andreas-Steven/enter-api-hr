<?php

declare(strict_types=1);

namespace App\Domain\PayrollDeduction\Repository;

// Domain Layer
use App\Domain\PayrollDeduction\Entity\PayrollDeduction;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * PayrollDeduction Repository Interface
 *
 * Pure repository pattern for PayrollDeduction aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface PayrollDeductionRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?PayrollDeduction;

    public function findByName(string $name, ?int $status = null): ?PayrollDeduction;

    public function existsByName(string $name, ?int $status = null): bool;

    /**
     * Find all PayrollDeduction records for a specific employee+period.
     *
     * Used by PayrollReportDomainService to include deduction data
     * in the payroll_summary.
     *
     * @return PayrollDeduction[]
     */
    public function findByEmployeeAndPeriod(int $employeeId, int $period, ?int $status = null): array;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function insert(PayrollDeduction $payrollDeduction): PayrollDeduction;

    public function update(PayrollDeduction $payrollDeduction): PayrollDeduction;

    public function delete(PayrollDeduction $payrollDeduction): PayrollDeduction;

    public function restore(int $id): ?PayrollDeduction;
}
