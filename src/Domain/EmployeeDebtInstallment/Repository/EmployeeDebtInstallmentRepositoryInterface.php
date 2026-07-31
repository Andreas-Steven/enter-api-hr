<?php

declare(strict_types=1);

namespace App\Domain\EmployeeDebtInstallment\Repository;

// Domain Layer
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * EmployeeDebtInstallment Repository Interface
 * 
 * Pure repository pattern for EmployeeDebtInstallment aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface EmployeeDebtInstallmentRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?EmployeeDebtInstallment;

    public function findByName(string $name, ?int $status = null): ?EmployeeDebtInstallment;

    public function existsByName(string $name, ?int $status = null): bool;

    /**
     * Ambil semua cicilan milik satu employee_debt (urut by sequence ASC).
     *
     * @return list<EmployeeDebtInstallment>
     */
    public function findByEmployeeDebtId(int $employeeDebtId, ?int $status = null): array;

    /**
     * Ambil cicilan yang masih pending (payment_status = 0) milik satu employee_debt.
     *
     * @return list<EmployeeDebtInstallment>
     */
    public function findPendingByEmployeeDebtId(int $employeeDebtId, ?int $status = null): array;

    /**
     * Ambil cicilan jatuh tempo pada periode YYYY-MM yang masih pending.
     * Dipakai payroll run untuk generate payroll_deduction otomatis.
     *
     * @param string $period Format YYYY-MM
     *
     * @return list<EmployeeDebtInstallment>
     */
    public function findDueForPeriod(string $period, ?int $status = null): array;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function insert(EmployeeDebtInstallment $employeeDebtInstallment): EmployeeDebtInstallment;

    public function update(EmployeeDebtInstallment $employeeDebtInstallment): EmployeeDebtInstallment;

    public function delete(EmployeeDebtInstallment $employeeDebtInstallment): EmployeeDebtInstallment;

    public function restore(int $id): ?EmployeeDebtInstallment;

}
