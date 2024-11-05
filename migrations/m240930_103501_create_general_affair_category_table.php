<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%general_affair_category}}`.
 */
class m240930_103501_create_general_affair_category_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%general_affair_category}}', [
            'id' => $this->primaryKey(),
            'name'  => $this->string(255)->notNull(),
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
        $this->addCommentOnColumn('{{%general_affair_category}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropCommentFromColumn('{{%general_affair_category}}', 'status');
        $this->dropTable('{{%general_affair_category}}');
    }
}
