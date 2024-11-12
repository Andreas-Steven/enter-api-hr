<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%work_time}}`.
 */
class m241007_032424_create_work_time_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%work_time}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'entry_time' => $this->time()->notNull(),
            'home_time' => $this->time()->notNull(),
            'working_days' => $this->json()->notNull(),
            'flexible' => $this->boolean()->defaultValue(false),
            'status' => $this->smallInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([]))
        ]);
        $this->addCommentOnColumn('{{%work_time}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
    }

    public function safeDown()
    {
        $this->dropTable('{{%work_time}}');
    }
}
