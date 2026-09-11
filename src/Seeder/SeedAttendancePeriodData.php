<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\AttendancePeriod\Entity\AttendancePeriod;
use App\Domain\AttendancePeriod\Repository\AttendancePeriodRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds AttendancePeriod table using Alice fixtures.
 */
final class SeedAttendancePeriodData extends AbstractSeederData
{
    private AttendancePeriodRepositoryInterface $repository;

    protected const YAML_FILE = 'attendanceperiod.yaml';
    protected const ENTITY_CLASS = AttendancePeriod::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendancePeriodRepositoryInterface $repository
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