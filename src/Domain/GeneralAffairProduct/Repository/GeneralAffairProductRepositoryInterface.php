<?php

declare(strict_types=1);

namespace App\Domain\GeneralAffairProduct\Repository;

// Domain Layer
use App\Domain\GeneralAffairProduct\Entity\GeneralAffairProduct;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * GeneralAffairProduct Repository Interface
 * 
 * Pure repository pattern for GeneralAffairProduct aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface GeneralAffairProductRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?GeneralAffairProduct;

    public function findByName(string $name, ?int $status = null): ?GeneralAffairProduct;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(GeneralAffairProduct $generalAffairProduct): GeneralAffairProduct;

    public function update(GeneralAffairProduct $generalAffairProduct): GeneralAffairProduct;

    public function delete(GeneralAffairProduct $generalAffairProduct): GeneralAffairProduct;

    public function restore(int $id): ?GeneralAffairProduct;

}
