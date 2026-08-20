<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\GeneralAffair\Entity\GeneralAffair;
// use App\Infrastructure\Persistence\GeneralAffair\GeneralAffairRepository;
use App\Domain\GeneralAffair\Repository\GeneralAffairRepositoryInterface;

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
 * Seeds generalaffair table using Alice fixtures.
 */
final class SeedGeneralAffairData extends AbstractSeederData
{
    private GeneralAffairRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'generalaffair.yaml';
    protected const ENTITY_CLASS = GeneralAffair::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        GeneralAffairRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = GeneralAffair::create(
            /* $entity->getName(), */
            productId: $entity->getProductId(),
            categoryId: $entity->getCategoryId(),
            locationId: $entity->getLocationId(),
            asset: $entity->getAsset(),
            status: $entity->getStatus(),
            detailInfo: $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
