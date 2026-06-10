<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\AttendanceCalendar\Entity\AttendanceCalendar;
// use App\Infrastructure\Persistence\AttendanceCalendar\AttendanceCalendarRepository;
use App\Domain\AttendanceCalendar\Repository\AttendanceCalendarRepositoryInterface;

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
 * Seeds attendancecalendar table using Alice fixtures.
 */
final class SeedAttendanceCalendarData extends AbstractSeederData
{
    private AttendanceCalendarRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'attendancecalendar.yaml';
    protected const ENTITY_CLASS = AttendanceCalendar::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendanceCalendarRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = AttendanceCalendar::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
