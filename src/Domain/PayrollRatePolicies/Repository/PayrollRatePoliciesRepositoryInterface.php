<?php

declare(strict_types=1);

namespace App\Domain\PayrollRatePolicies\Repository;

// Domain Layer
use App\Domain\PayrollRatePolicies\Entity\PayrollRatePolicies;
use App\Domain\Shared\Enums\PayrollRateType;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * PayrollRatePolicies Repository Interface
 *
 * Pure repository pattern for PayrollRatePolicies aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface PayrollRatePoliciesRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?PayrollRatePolicies;

    public function findByRateType(PayrollRateType $rateType, ?int $status = null): ?PayrollRatePolicies;

    public function findByName(string $name, ?int $status = null): ?PayrollRatePolicies;

    public function existsByName(string $name, ?int $status = null): bool;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function insert(PayrollRatePolicies $PayrollRatePolicies): PayrollRatePolicies;

    public function update(PayrollRatePolicies $PayrollRatePolicies): PayrollRatePolicies;

    public function delete(PayrollRatePolicies $PayrollRatePolicies): PayrollRatePolicies;

    public function restore(int $id): ?PayrollRatePolicies;
}
