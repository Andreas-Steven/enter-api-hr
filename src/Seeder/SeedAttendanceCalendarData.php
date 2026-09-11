<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\AttendanceCalendar\Entity\AttendanceCalendar;
use App\Domain\AttendanceCalendar\Repository\AttendanceCalendarRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds AttendanceCalendar table using Alice fixtures.
 */
final class SeedAttendanceCalendarData extends AbstractSeederData
{
    private AttendanceCalendarRepositoryInterface $repository;

    protected const YAML_FILE = 'attendancecalendar.yaml';
    protected const ENTITY_CLASS = AttendanceCalendar::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendanceCalendarRepositoryInterface $repository
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