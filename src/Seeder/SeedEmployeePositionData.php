<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\EmployeePosition\Entity\EmployeePosition;
use App\Domain\EmployeePosition\Repository\EmployeePositionRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds EmployeePosition table using Alice fixtures.
 */
final class SeedEmployeePositionData extends AbstractSeederData
{
    private EmployeePositionRepositoryInterface $repository;

    protected const YAML_FILE = 'employeeposition.yaml';
    protected const ENTITY_CLASS = EmployeePosition::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeePositionRepositoryInterface $repository
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