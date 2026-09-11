<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\EmployeeDivision\Entity\EmployeeDivision;
use App\Domain\EmployeeDivision\Repository\EmployeeDivisionRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds EmployeeDivision table using Alice fixtures.
 */
final class SeedEmployeeDivisionData extends AbstractSeederData
{
    private EmployeeDivisionRepositoryInterface $repository;

    protected const YAML_FILE = 'employeedivision.yaml';
    protected const ENTITY_CLASS = EmployeeDivision::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeeDivisionRepositoryInterface $repository
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