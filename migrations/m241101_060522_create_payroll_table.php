<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%payroll}}`.
 */
class m241101_060522_create_payroll_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%payroll}}', [
            'id' => $this->primaryKey(),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%payroll}}');
    }
}
