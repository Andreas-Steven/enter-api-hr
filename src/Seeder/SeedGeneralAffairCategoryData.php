<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\GeneralAffairCategory\Entity\GeneralAffairCategory;
// use App\Infrastructure\Persistence\GeneralAffairCategory\GeneralAffairCategoryRepository;
use App\Domain\GeneralAffairCategory\Repository\GeneralAffairCategoryRepositoryInterface;

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
 * Seeds generalaffaircategory table using Alice fixtures.
 */
final class SeedGeneralAffairCategoryData extends AbstractSeederData
{
    private GeneralAffairCategoryRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'generalaffaircategory.yaml';
    protected const ENTITY_CLASS = GeneralAffairCategory::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        GeneralAffairCategoryRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = GeneralAffairCategory::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
