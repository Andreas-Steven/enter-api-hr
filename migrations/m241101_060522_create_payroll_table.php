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
            'status' => $this->smallInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([]))
        ]);
        $this->addCommentOnColumn('{{%payroll}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropCommentFromColumn('{{%payroll}}', 'status');
        $this->dropTable('{{%payroll}}');
    }
}
