<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\WorkTime\Entity\WorkTime;
// use App\Infrastructure\Persistence\WorkTime\WorkTimeRepository;
use App\Domain\WorkTime\Repository\WorkTimeRepositoryInterface;

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
 * Seeds worktime table using Alice fixtures.
 */
final class SeedWorkTimeData extends AbstractSeederData
{
    private WorkTimeRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'worktime.yaml';
    protected const ENTITY_CLASS = WorkTime::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        WorkTimeRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = WorkTime::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
