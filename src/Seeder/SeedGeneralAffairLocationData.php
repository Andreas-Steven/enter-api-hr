<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\GeneralAffairLocation\Entity\GeneralAffairLocation;
use App\Domain\GeneralAffairLocation\Repository\GeneralAffairLocationRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds GeneralAffairLocation table using Alice fixtures.
 */
final class SeedGeneralAffairLocationData extends AbstractSeederData
{
    private GeneralAffairLocationRepositoryInterface $repository;

    protected const YAML_FILE = 'generalaffairlocation.yaml';
    protected const ENTITY_CLASS = GeneralAffairLocation::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        GeneralAffairLocationRepositoryInterface $repository
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