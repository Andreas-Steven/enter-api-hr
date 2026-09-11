<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\PayrollRatePolicies\Entity\PayrollRatePolicies;
use App\Domain\PayrollRatePolicies\Repository\PayrollRatePoliciesRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds PayrollRatePolicies table using Alice fixtures.
 */
final class SeedPayrollRatePoliciesData extends AbstractSeederData
{
    private PayrollRatePoliciesRepositoryInterface $repository;

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
        $entity->updateDetailInfo($detailInfo);
        $this->repository->insert($entity);
    }
}