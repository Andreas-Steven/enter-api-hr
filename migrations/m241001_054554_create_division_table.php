<?php

use yii\db\Migration;

class m241001_054554_create_division_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%division}}', [
            'id'        => $this->primaryKey(),
            'code'      => $this->string(255)->notNull(),
            'name'      => $this->string(255)->notNull(),
            'status'    => $this->smallInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([]))
        ]);
        $this->addCommentOnColumn('{{%division}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
    }

    public function safeDown()
    {
        $this->dropTable('{{%division}}');
    }
}
