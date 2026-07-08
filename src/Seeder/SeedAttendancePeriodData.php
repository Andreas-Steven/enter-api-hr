<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\AttendancePeriod\Entity\AttendancePeriod;
// use App\Infrastructure\Persistence\AttendancePeriod\AttendancePeriodRepository;
use App\Domain\AttendancePeriod\Repository\AttendancePeriodRepositoryInterface;

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
 * Seeds attendanceperiod table using Alice fixtures.
 */
final class SeedAttendancePeriodData extends AbstractSeederData
{
    private AttendancePeriodRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'attendanceperiod.yaml';
    protected const ENTITY_CLASS = AttendancePeriod::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendancePeriodRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = AttendancePeriod::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
