<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\EmployeeLeaveType\Entity\EmployeeLeaveType;
// use App\Infrastructure\Persistence\EmployeeLeaveType\EmployeeLeaveTypeRepository;
use App\Domain\EmployeeLeaveType\Repository\EmployeeLeaveTypeRepositoryInterface;

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
 * Seeds employeeleavetype table using Alice fixtures.
 */
final class SeedEmployeeLeaveTypeData extends AbstractSeederData
{
    private EmployeeLeaveTypeRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'employeeleavetype.yaml';
    protected const ENTITY_CLASS = EmployeeLeaveType::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeeLeaveTypeRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = EmployeeLeaveType::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
