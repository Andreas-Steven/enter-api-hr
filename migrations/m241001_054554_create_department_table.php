<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%general_affair_department}}`.
 */
class m241001_054554_create_department_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%department}}', [
            'id'        => $this->primaryKey(),
            'code'      => $this->string(255)->notNull(),
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
        $this->addCommentOnColumn('{{%department}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropCommentFromColumn('{{%department}}', 'status');
        $this->dropTable('{{%department}}');
    }
}
