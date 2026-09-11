<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\EmployeeType\Entity\EmployeeType;
use App\Domain\EmployeeType\Repository\EmployeeTypeRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds EmployeeType table using Alice fixtures.
 */
final class SeedEmployeeTypeData extends AbstractSeederData
{
    private EmployeeTypeRepositoryInterface $repository;

    protected const YAML_FILE = 'employeetype.yaml';
    protected const ENTITY_CLASS = EmployeeType::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeeTypeRepositoryInterface $repository
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