<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\EmployeeLeave\Entity\EmployeeLeave;
use App\Domain\EmployeeLeave\Repository\EmployeeLeaveRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds EmployeeLeave table using Alice fixtures.
 */
final class SeedEmployeeLeaveData extends AbstractSeederData
{
    private EmployeeLeaveRepositoryInterface $repository;

    protected const YAML_FILE = 'employeeleave.yaml';
    protected const ENTITY_CLASS = EmployeeLeave::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeeLeaveRepositoryInterface $repository
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