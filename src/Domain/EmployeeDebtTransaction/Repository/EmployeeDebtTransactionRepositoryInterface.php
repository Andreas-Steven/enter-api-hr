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
     * Ambil semua transaksi (ledger) milik satu employee_debt, urut by transaction_date ASC.
     *
     * @return list<EmployeeDebtTransaction>
     */
    public function findByEmployeeDebtId(int $employeeDebtId, ?int $status = null): array;

    /**
     * Ambil transaksi yang menandai pembayaran untuk satu installment spesifik.
     *
     * @return list<EmployeeDebtTransaction>
     */
    public function findByEmployeeDebtInstallmentId(int $installmentId, ?int $status = null): array;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function insert(EmployeeDebtTransaction $employeeDebtTransaction): EmployeeDebtTransaction;

    public function update(EmployeeDebtTransaction $employeeDebtTransaction): EmployeeDebtTransaction;

    public function delete(EmployeeDebtTransaction $employeeDebtTransaction): EmployeeDebtTransaction;

    public function restore(int $id): ?EmployeeDebtTransaction;

}
