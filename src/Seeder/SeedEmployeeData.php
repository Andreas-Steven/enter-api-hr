<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\Employee\Entity\Employee;
// use App\Infrastructure\Persistence\Employee\EmployeeRepository;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;

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
 * Seeds employee table using Alice fixtures.
 */
final class SeedEmployeeData extends AbstractSeederData
{
    private EmployeeRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'employee.yaml';
    protected const ENTITY_CLASS = Employee::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeeRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = Employee::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
