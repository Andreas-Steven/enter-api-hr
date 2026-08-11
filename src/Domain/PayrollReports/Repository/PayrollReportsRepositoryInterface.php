<?php

declare(strict_types=1);

namespace App\Domain\PayrollReports\Repository;

// Domain Layer
use App\Domain\PayrollReports\Entity\PayrollReports;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * PayrollReports Repository Interface
 * 
 * Pure repository pattern for PayrollReports aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface PayrollReportsRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?PayrollReports;

    public function findByName(string $name, ?int $status = null): ?PayrollReports;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(PayrollReports $payrollReports): PayrollReports;

    public function update(PayrollReports $payrollReports): PayrollReports;

    public function delete(PayrollReports $payrollReports): PayrollReports;

    public function restore(int $id): ?PayrollReports;

}
