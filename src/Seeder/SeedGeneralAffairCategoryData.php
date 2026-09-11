<?php

declare(strict_types=1);

namespace App\Seeder;

use App\Domain\GeneralAffairCategory\Entity\GeneralAffairCategory;
use App\Domain\GeneralAffairCategory\Repository\GeneralAffairCategoryRepositoryInterface;
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds GeneralAffairCategory table using Alice fixtures.
 */
final class SeedGeneralAffairCategoryData extends AbstractSeederData
{
    private GeneralAffairCategoryRepositoryInterface $repository;

    protected const YAML_FILE = 'generalaffaircategory.yaml';
    protected const ENTITY_CLASS = GeneralAffairCategory::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        GeneralAffairCategoryRepositoryInterface $repository
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