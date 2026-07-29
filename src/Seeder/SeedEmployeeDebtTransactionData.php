<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;
// use App\Infrastructure\Persistence\EmployeeDebtTransaction\EmployeeDebtTransactionRepository;
use App\Domain\EmployeeDebtTransaction\Repository\EmployeeDebtTransactionRepositoryInterface;

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
 * Seeds employeedebttransaction table using Alice fixtures.
 */
final class SeedEmployeeDebtTransactionData extends AbstractSeederData
{
    private EmployeeDebtTransactionRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'employeedebttransaction.yaml';
    protected const ENTITY_CLASS = EmployeeDebtTransaction::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeeDebtTransactionRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = EmployeeDebtTransaction::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
