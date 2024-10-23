<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%absensi}}`.
 */
class m241007_063531_create_absensi_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%absensi}}', [
            'id' => $this->primaryKey(),
            'date' => $this->date()->notNull(),
            'dayoff' => $this->boolean()->notNull()->defaultValue(false),
            'attendance' => $this->json()->notNull()->defaultValue(json_encode([])),
            'blacklist' => $this->json()->notNull()->defaultValue(json_encode([])),
            'status' => $this->smallInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([]))
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%absensi}}');
    }
}
