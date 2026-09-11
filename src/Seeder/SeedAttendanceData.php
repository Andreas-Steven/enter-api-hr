<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\Attendance\Entity\Attendance;
use App\Domain\Attendance\Repository\AttendanceRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds Attendance table using Alice fixtures.
 */
final class SeedAttendanceData extends AbstractSeederData
{
    private AttendanceRepositoryInterface $repository;

    protected const YAML_FILE = 'attendance.yaml';
    protected const ENTITY_CLASS = Attendance::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendanceRepositoryInterface $repository
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