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
            'attendance' => $this->json()->notNull()->defaultValue(json_encode([])),
            'status' => $this->smallInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([]))
        ]);
        $this->addCommentOnColumn('{{%absensi}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropCommentFromColumn('{{%absensi}}', 'status');
        $this->dropTable('{{%absensi}}');
    }
}
