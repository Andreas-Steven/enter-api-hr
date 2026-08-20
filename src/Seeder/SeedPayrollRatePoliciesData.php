<?php

declare(strict_types=1);

namespace App\Seeder;

// Domain Layer
use App\Domain\PayrollRatePolicies\Entity\PayrollRatePolicies;
use App\Domain\PayrollRatePolicies\Repository\PayrollRatePoliciesRepositoryInterface;

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
 * Seeds payroll_rate_policies table using Alice fixtures.
 */
final class SeedPayrollRatePoliciesData extends AbstractSeederData
{
    private PayrollRatePoliciesRepositoryInterface $repository;

    // Fixture constants
    protected const YAML_FILE = 'PayrollRatePolicies.yaml';
    protected const ENTITY_CLASS = PayrollRatePolicies::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        PayrollRatePoliciesRepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        // Create new entity with proper DetailInfo
        $newEntity = PayrollRatePolicies::create(
            $entity->getName(),
            $entity->getStatus(),
            $detailInfo
        );

        // Use repository to insert
        $this->repository->insert($newEntity);
    }
}
