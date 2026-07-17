<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\DeductionType\Entity\DeductionType;
// use App\Infrastructure\Persistence\DeductionType\DeductionTypeRepository;
use App\Domain\DeductionType\Repository\DeductionTypeRepositoryInterface;

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
 * Seeds deductiontype table using Alice fixtures.
 */
final class SeedDeductionTypeData extends AbstractSeederData
{
    private DeductionTypeRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'deductiontype.yaml';
    protected const ENTITY_CLASS = DeductionType::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        DeductionTypeRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = DeductionType::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
