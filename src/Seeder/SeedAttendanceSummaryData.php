<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\AttendanceSummary\Entity\AttendanceSummary;
use App\Domain\AttendanceSummary\Repository\AttendanceSummaryRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds AttendanceSummary table using Alice fixtures.
 */
final class SeedAttendanceSummaryData extends AbstractSeederData
{
    private AttendanceSummaryRepositoryInterface $repository;

    protected const YAML_FILE = 'AttendanceSummary.yaml';
    protected const ENTITY_CLASS = AttendanceSummary::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendanceSummaryRepositoryInterface $repository
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