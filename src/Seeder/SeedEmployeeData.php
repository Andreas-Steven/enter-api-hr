<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\Employee\Entity\Employee;
use App\Domain\Employee\Repository\EmployeeRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds Employee table using Alice fixtures.
 */
final class SeedEmployeeData extends AbstractSeederData
{
    private EmployeeRepositoryInterface $repository;

    protected const YAML_FILE = 'employee.yaml';
    protected const ENTITY_CLASS = Employee::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeeRepositoryInterface $repository
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