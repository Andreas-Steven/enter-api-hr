<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\EmployeeLeave\Entity\EmployeeLeave;
// use App\Infrastructure\Persistence\EmployeeLeave\EmployeeLeaveRepository;
use App\Domain\EmployeeLeave\Repository\EmployeeLeaveRepositoryInterface;

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
 * Seeds employeeleave table using Alice fixtures.
 */
final class SeedEmployeeLeaveData extends AbstractSeederData
{
    private EmployeeLeaveRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'employeeleave.yaml';
    protected const ENTITY_CLASS = EmployeeLeave::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeeLeaveRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = EmployeeLeave::create(
            $entity->getName(),
            $entity->getLeaveType(),
            $entity->getEmployeeId(),
            $entity->getPeriodStartDate(),
            $entity->getPeriodEndDate(),
            $entity->getTotalAmount(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
