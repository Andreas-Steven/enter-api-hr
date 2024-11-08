<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%user_attendance}}`.
 */
class m241108_100511_create_user_attendance_table extends Migration
{
    public function safeUp()
    {
        $this->createTable('{{%user_attendance}}', [
            'id' => $this->primaryKey(),
            'employee_id' => $this->integer()->notNull(),
            'work_time_id' => $this->integer()->notNull(),
            'status' => $this->tinyInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([]))
        ]);
        $this->addCommentOnColumn('{{%work_time}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
        $this->addForeignKey(
            'fk-users-employee_id',
            '{{%user_attendance}}',
            'employee_id',
            '{{%employee}}',
            'id',
            'CASCADE'
        );
        $this->addForeignKey(
            'fk-users-work_time_id',
            '{{%user_attendance}}',
            'work_time_id',
            '{{%work_time}}',
            'id',
            'CASCADE'
        );
    }

    public function safeDown()
    {
        $this->dropTable('{{%user_attendance}}');
    }
}
