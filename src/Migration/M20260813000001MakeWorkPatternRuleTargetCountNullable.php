<?php

declare(strict_types=1);

namespace App\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

final class M20260813000001MakeWorkPatternRuleTargetCountNullable implements RevertibleMigrationInterface
{
    private const TABLE_NAME = 'work_pattern_rules';

    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->alterColumn(
            self::TABLE_NAME,
            'target_count',
            $cb::integer()->null(),
        );

        $b->update(
            self::TABLE_NAME,
            ['target_count' => null],
            ['rule_type' => 'REQUIRED_WEEKDAY'],
        );
    }

    public function down(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->update(
            self::TABLE_NAME,
            ['target_count' => 0],
            ['target_count' => null],
        );

        $b->alterColumn(
            self::TABLE_NAME,
            'target_count',
            $cb::integer()->notNull()->defaultValue(0),
        );
    }
}
