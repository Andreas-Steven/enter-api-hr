<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\AttendancePermission\Entity\AttendancePermission;
use App\Domain\AttendancePermission\Repository\AttendancePermissionRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds AttendancePermission table using Alice fixtures.
 */
final class SeedAttendancePermissionData extends AbstractSeederData
{
    private AttendancePermissionRepositoryInterface $repository;

    protected const YAML_FILE = 'attendancepermission.yaml';
    protected const ENTITY_CLASS = AttendancePermission::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendancePermissionRepositoryInterface $repository
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