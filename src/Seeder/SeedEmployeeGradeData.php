<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\EmployeeGrade\Entity\EmployeeGrade;
use App\Domain\EmployeeGrade\Repository\EmployeeGradeRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds EmployeeGrade table using Alice fixtures.
 */
final class SeedEmployeeGradeData extends AbstractSeederData
{
    private EmployeeGradeRepositoryInterface $repository;

    protected const YAML_FILE = 'employeegrade.yaml';
    protected const ENTITY_CLASS = EmployeeGrade::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        EmployeeGradeRepositoryInterface $repository
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