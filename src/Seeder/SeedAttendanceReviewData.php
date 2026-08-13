<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\AttendanceReview\Entity\AttendanceReview;
// use App\Infrastructure\Persistence\AttendanceReview\AttendanceReviewRepository;
use App\Domain\AttendanceReview\Repository\AttendanceReviewRepositoryInterface;

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
 * Seeds attendancereview table using Alice fixtures.
 */
final class SeedAttendanceReviewData extends AbstractSeederData
{
    private AttendanceReviewRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'attendancereview.yaml';
    protected const ENTITY_CLASS = AttendanceReview::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendanceReviewRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = AttendanceReview::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
