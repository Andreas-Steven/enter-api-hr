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
        his->addCommentOnColumn('{{%general_affair}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%payroll}}');
        $this->dropCommentFromColumn('{{%general_affair}}', 'status');
    }
}
