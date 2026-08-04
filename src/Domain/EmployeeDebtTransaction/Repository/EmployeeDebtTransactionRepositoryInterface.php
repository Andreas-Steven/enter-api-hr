<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebtTransaction\Repository;

// Domain Layer
use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * EmployeeDebtTransaction Repository Interface
 *
 * Pure repository pattern for EmployeeDebtTransaction aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface EmployeeDebtTransactionRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?EmployeeDebtTransaction;

    public function findByName(string $name, ?int $status = null): ?EmployeeDebtTransaction;

    public function existsByName(string $name, ?int $status = null): bool;

    /**
     * Ambil semua transaksi untuk satu EmployeeDebt.
     *
     * @return list<EmployeeDebtTransaction>
     */
    public function findByEmployeeDebtId(int $employeeDebtId, ?int $status = null): array;

    /**
     * Cek apakah transaksi sudah ada untuk PayrollDeduction tertentu.
     * Dipakai untuk mencegah double-transaction saat payroll run.
     */
    public function existsForPayrollDeduction(int $payrollDeductionId, ?int $status = null): bool;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function insert(EmployeeDebtTransaction $entity): EmployeeDebtTransaction;

    public function update(EmployeeDebtTransaction $entity): EmployeeDebtTransaction;

    public function delete(EmployeeDebtTransaction $entity): EmployeeDebtTransaction;

    public function restore(int $id): ?EmployeeDebtTransaction;
}
