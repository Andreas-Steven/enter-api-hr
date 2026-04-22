<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\GeneralAffairLocation\Entity\GeneralAffairLocation;
// use App\Infrastructure\Persistence\GeneralAffairLocation\GeneralAffairLocationRepository;
use App\Domain\GeneralAffairLocation\Repository\GeneralAffairLocationRepositoryInterface;

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
 * Seeds generalaffairlocation table using Alice fixtures.
 */
final class SeedGeneralAffairLocationData extends AbstractSeederData
{
    private GeneralAffairLocationRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'generalaffairlocation.yaml';
    protected const ENTITY_CLASS = GeneralAffairLocation::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        GeneralAffairLocationRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = GeneralAffairLocation::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
