<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\AttendanceDevice\Entity\AttendanceDevice;
use App\Domain\AttendanceDevice\Repository\AttendanceDeviceRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds AttendanceDevice table using Alice fixtures.
 */
final class SeedAttendanceDeviceData extends AbstractSeederData
{
    private AttendanceDeviceRepositoryInterface $repository;

    protected const YAML_FILE = 'attendancedevice.yaml';
    protected const ENTITY_CLASS = AttendanceDevice::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendanceDeviceRepositoryInterface $repository
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