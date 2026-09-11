<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\EmployeeDebtTransaction\Entity\EmployeeDebtTransaction;
use App\Domain\EmployeeDebtTransaction\Repository\EmployeeDebtTransactionRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds EmployeeDebtTransaction table using Alice fixtures.
 */
final class SeedEmployeeDebtTransactionData extends AbstractSeederData
{
    private EmployeeDebtTransactionRepositoryInterface $repository;

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
        $entity->updateDetailInfo($detailInfo);
        $this->repository->insert($entity);
    }
}