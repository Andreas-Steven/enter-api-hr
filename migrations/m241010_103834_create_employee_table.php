<?php

use yii\db\Migration;

class m241010_103834_create_employee_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%employee}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(255)->notNull(),
            'nik' => $this->string(255),
            'contact' => $this->json()->defaultValue(json_encode([])),
            'birth_date' => $this->date(),
            'date_start' => $this->date(),
            'date_resign' => $this->date(),
            'sso_id' => $this->integer()->notNull(),
            // 'division_id' => $this->integer()->notNull(),
            'division_id' => $this->integer(),
            'status' => $this->smallInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([])),
        ]);

        // $this->addForeignKey(
        //     'fk_employee_division', // Simplified foreign key name
        //     '{{%employee}}',
        //     'division_id',
        //     '{{%division}}',
        //     'id',
        //     'CASCADE'
        // );
        
        $this->addCommentOnColumn('{{%employee}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
    }

    public function safeDown()
    {
        $this->dropTable('{{%employee}}');
    }
}
