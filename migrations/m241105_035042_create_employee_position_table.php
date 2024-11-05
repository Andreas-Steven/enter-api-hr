<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%employee_position}}`.
 */
class m241105_035042_create_employee_position_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%employee_position}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([]))
        ]);
        $this->addCommentOnColumn('{{%employee_position}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropCommentFromColumn('{{%employee_position}}', 'status');
        $this->dropTable('{{%employee_position}}');
    }
}
