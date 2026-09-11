<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\PayrollDeductionType\Entity\PayrollDeductionType;
use App\Domain\PayrollDeductionType\Repository\PayrollDeductionTypeRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds PayrollDeductionType table using Alice fixtures.
 */
final class SeedPayrollDeductionTypeData extends AbstractSeederData
{
    private PayrollDeductionTypeRepositoryInterface $repository;

    protected const YAML_FILE = 'payrolldeductiontype.yaml';
    protected const ENTITY_CLASS = PayrollDeductionType::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        PayrollDeductionTypeRepositoryInterface $repository
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