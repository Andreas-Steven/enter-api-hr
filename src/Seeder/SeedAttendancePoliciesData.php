<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\AttendancePolicies\Entity\AttendancePolicies;
use App\Domain\AttendancePolicies\Repository\AttendancePoliciesRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds AttendancePolicies table using Alice fixtures.
 */
final class SeedAttendancePoliciesData extends AbstractSeederData
{
    private AttendancePoliciesRepositoryInterface $repository;

    protected const YAML_FILE = 'attendancepolicies.yaml';
    protected const ENTITY_CLASS = AttendancePolicies::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        AttendancePoliciesRepositoryInterface $repository
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