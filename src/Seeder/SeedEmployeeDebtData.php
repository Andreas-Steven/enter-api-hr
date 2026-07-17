<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\EmployeeDebt\Entity\EmployeeDebt;
// use App\Infrastructure\Persistence\EmployeeDebt\EmployeeDebtRepository;
use App\Domain\EmployeeDebt\Repository\EmployeeDebtRepositoryInterface;

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
 * Seeds employeedebt table using Alice fixtures.
 */
final class SeedEmployeeDebtData extends AbstractSeederData
{
    private EmployeeDebtRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'employeedebt.yaml';
    protected const ENTITY_CLASS = EmployeeDebt::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeeDebtRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = EmployeeDebt::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
