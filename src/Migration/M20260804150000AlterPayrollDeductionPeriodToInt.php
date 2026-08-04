<?php

declare(strict_types=1);

namespace App\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

/**
 * Alter payroll_deduction: ubah kolom period dari string(20) ke integer.
 *
 * Data existing dengan format YYYY-MM (mis. "2026-08") akan dikonversi
 * ke format YYYYMM (mis. 202608) menggunakan USING clause.
 */
final class M20260804150000AlterPayrollDeductionPeriodToInt implements RevertibleMigrationInterface
{
    private const TABLE_NAME = 'payroll_deduction';

    public function up(MigrationBuilder $b): void
    {
        // Konversi data "YYYY-MM" -> YYYYMM, lalu alter column type ke integer
        $b->execute(
            'ALTER TABLE ' . self::TABLE_NAME . ' '
            . 'ALTER COLUMN period TYPE integer '
            . 'USING CAST(REPLACE(period, \'-\', \'\') AS integer)'
        );
    }

    public function down(MigrationBuilder $b): void
    {
        // Kembalikan ke string(20) dengan format YYYY-MM
        $b->execute(
            'ALTER TABLE ' . self::TABLE_NAME . ' '
            . 'ALTER COLUMN period TYPE varchar(20) '
            . "USING TO_CHAR(period, 'YYYY-MM')"
        );
    }
}
