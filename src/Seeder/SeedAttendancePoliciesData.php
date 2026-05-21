<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\AttendancePolicies\Entity\AttendancePolicies;
// use App\Infrastructure\Persistence\AttendancePolicies\AttendancePoliciesRepository;
use App\Domain\AttendancePolicies\Repository\AttendancePoliciesRepositoryInterface;

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
 * Seeds attendancepolicies table using Alice fixtures.
 */
final class SeedAttendancePoliciesData extends AbstractSeederData
{
    private AttendancePoliciesRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'attendancepolicies.yaml';
    protected const ENTITY_CLASS = AttendancePolicies::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendancePoliciesRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = AttendancePolicies::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
