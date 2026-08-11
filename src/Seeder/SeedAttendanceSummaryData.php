<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
// use App\Infrastructure\Persistence\AttendanceSummary\AttendanceSummaryRepository;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;

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
 * Seeds AttendanceSummary table using Alice fixtures.
 */
final class SeedAttendanceSummaryData extends AbstractSeederData
{
    private AttendanceSummaryRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'AttendanceSummary.yaml';
    protected const ENTITY_CLASS = AttendanceSummary::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendanceSummaryRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = AttendanceSummary::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
