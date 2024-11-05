<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%work_time}}`.
 */
class m241007_032424_create_work_time_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%work_time}}', [
            'id' => $this->primaryKey(),
            'entry_time' => $this->time()->notNull(),
            'home_time' => $this->time()->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([
                'change_log' => [
                    'created_at' => null,
                    'created_by' => null,
                    'deleted_by' => null,
                    'deleted_at' => null,
                    'updated_at' => null,
                    'updated_by' => null
                ]
            ]))
        ]);
        $this->addCommentOnColumn('{{%work_time}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropCommentFromColumn('{{%work_time}}', 'status');
        $this->dropTable('{{%work_time}}');
    }
}
