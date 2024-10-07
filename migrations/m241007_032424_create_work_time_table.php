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
            'name' => $this->string(255)->notNull(),
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
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%work_time}}');
    }
}
