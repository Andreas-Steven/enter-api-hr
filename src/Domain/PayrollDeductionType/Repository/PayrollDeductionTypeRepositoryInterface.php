<?php

declare(strict_types=1);

namespace App\Domain\PayrollDeductionType\Repository;

// Domain Layer
use App\Domain\PayrollDeductionType\Entity\PayrollDeductionType;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * PayrollDeductionType Repository Interface
 * 
 * Pure repository pattern for PayrollDeductionType aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface PayrollDeductionTypeRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?PayrollDeductionType;

    public function findByName(string $name, ?int $status = null): ?PayrollDeductionType;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(PayrollDeductionType $deductionType): PayrollDeductionType;

    public function update(PayrollDeductionType $deductionType): PayrollDeductionType;

    public function delete(PayrollDeductionType $deductionType): PayrollDeductionType;

    public function restore(int $id): ?PayrollDeductionType;

}
