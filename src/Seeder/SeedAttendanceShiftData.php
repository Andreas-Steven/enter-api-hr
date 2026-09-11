<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\AttendanceShift\Entity\AttendanceShift;
use App\Domain\AttendanceShift\Repository\AttendanceShiftRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds AttendanceShift table using Alice fixtures.
 */
final class SeedAttendanceShiftData extends AbstractSeederData
{
    private AttendanceShiftRepositoryInterface $repository;

    protected const YAML_FILE = 'attendanceshift.yaml';
    protected const ENTITY_CLASS = AttendanceShift::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendanceShiftRepositoryInterface $repository
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