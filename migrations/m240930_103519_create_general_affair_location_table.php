<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%general_affair_location}}`.
 */
class m240930_103519_create_general_affair_location_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%general_affair_location}}', [
            'id'        => $this->primaryKey(),
            'name'      => $this->string(255)->notNull(),
            'status'    => $this->smallInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([
                'change_log' => [
                    'created_at' => null,
                    'created_by' => null,
                    'deleted_at' => null,
                    'deleted_by' => null,
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
        $this->dropTable('{{%general_affair_location}}');
    }
}
