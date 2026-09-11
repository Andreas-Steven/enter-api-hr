<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\PayrollDeduction\Entity\PayrollDeduction;
use App\Domain\PayrollDeduction\Repository\PayrollDeductionRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds PayrollDeduction table using Alice fixtures.
 */
final class SeedPayrollDeductionData extends AbstractSeederData
{
    private PayrollDeductionRepositoryInterface $repository;

    protected const YAML_FILE = 'payrolldeduction.yaml';
    protected const ENTITY_CLASS = PayrollDeduction::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        PayrollDeductionRepositoryInterface $repository
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