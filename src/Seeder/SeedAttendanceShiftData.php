<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\AttendanceShift\Entity\AttendanceShift;
// use App\Infrastructure\Persistence\AttendanceShift\AttendanceShiftRepository;
use App\Domain\AttendanceShift\Repository\AttendanceShiftRepositoryInterface;

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
 * Seeds attendanceshift table using Alice fixtures.
 */
final class SeedAttendanceShiftData extends AbstractSeederData
{
    private AttendanceShiftRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'attendanceshift.yaml';
    protected const ENTITY_CLASS = AttendanceShift::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendanceShiftRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = AttendanceShift::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
