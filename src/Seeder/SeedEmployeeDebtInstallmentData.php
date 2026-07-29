<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\EmployeeDebtInstallment\Entity\EmployeeDebtInstallment;
// use App\Infrastructure\Persistence\EmployeeDebtInstallment\EmployeeDebtInstallmentRepository;
use App\Domain\EmployeeDebtInstallment\Repository\EmployeeDebtInstallmentRepositoryInterface;

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
 * Seeds employeedebtinstallment table using Alice fixtures.
 */
final class SeedEmployeeDebtInstallmentData extends AbstractSeederData
{
    private EmployeeDebtInstallmentRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'employeedebtinstallment.yaml';
    protected const ENTITY_CLASS = EmployeeDebtInstallment::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeeDebtInstallmentRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = EmployeeDebtInstallment::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
