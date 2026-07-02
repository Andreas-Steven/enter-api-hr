<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\Branch\Entity\Branch;
// use App\Infrastructure\Persistence\Branch\BranchRepository;
use App\Domain\Branch\Repository\BranchRepositoryInterface;

// PSR Interfaces
use Psr\Clock\ClockInterface;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;

// Application Layer
use App\Application\Shared\Factory\DetailInfoFactory;
use App\Shared\Query\QueryConditionApplier;

// Infrastructure Layer
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds branch table using Alice fixtures.
 */
final class SeedBranchData extends AbstractSeederData
{
    private BranchRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'branch.yaml';
    protected const ENTITY_CLASS = Branch::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        BranchRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = Branch::create(
            name: $entity->getName(),
            code: $entity->getCode(),
            address: $entity->getAddress(),
            attendancePoliciesId: $entity->getAttendancePoliciesId(),
            status: $entity->getStatus(),
            branchType: $entity->getType(),
            detailInfo: $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
