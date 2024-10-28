<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%general_affair}}`.
 */
class m241001_072940_create_general_affair_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%general_affair}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'category_id' => $this->integer()->notNull(),
            'location_id' => $this->integer()->notNull(),
            'status' => $this->smallInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([])),
            'item' => $this->json()->notNull()->defaultValue(json_encode([])),
        ]);

        $this->addForeignKey(
            '{{%general_affair_category_id_fk}}',
            '{{%general_affair}}',
            'category_id',
            '{{%general_affair_category}}',
            'id',
            'CASCADE'
        );
        $this->addForeignKey(
            '{{%general_affair_location_id_fk}}',
            '{{%general_affair}}',
            'location_id',
            '{{%general_affair_location}}',
            'id',
            'CASCADE'
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropTable('{{%general_affair}}');
    }
}
