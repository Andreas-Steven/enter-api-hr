<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%device}}`.
 */
class m241007_045814_create_device_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%device}}', [
            'id' => $this->primaryKey(),
            'device_code' => $this->string(5)->notNull(),
            'device_name' => $this->string(255)->notNull(),
            'device_address' => $this->string(255)->notNull(),
            'device_ip' => $this->string(15)->notNull(),
            'device_location' => $this->json()->notNull()->defaultValue(json_encode([
                'lat' => null,
                'long' => null
            ])),
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
        $this->dropTable('{{%device}}');
    }
}
