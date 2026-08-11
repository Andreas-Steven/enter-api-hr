<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\PayrollReports\Entity\PayrollReports;
// use App\Infrastructure\Persistence\PayrollReports\PayrollReportsRepository;
use App\Domain\PayrollReports\Repository\PayrollReportsRepositoryInterface;

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
 * Seeds payrollreports table using Alice fixtures.
 */
final class SeedPayrollReportsData extends AbstractSeederData
{
    private PayrollReportsRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'payrollreports.yaml';
    protected const ENTITY_CLASS = PayrollReports::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        PayrollReportsRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = PayrollReports::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );
        
        // Use repository to insert
        $this->repository->insert($newEntity);   
    }
}
