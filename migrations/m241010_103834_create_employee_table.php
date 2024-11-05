<?php

use yii\db\Migration;

/**
 * Handles the creation of table `{{%employee}}`.
 */
class m241010_103834_create_employee_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('{{%employee}}', [
            'id'                    => $this->primaryKey(),
            'name'                  => $this->string(255)->notNull(),
            'employee_detail' => $this->json()->notNull()->defaultValue(json_encode([
                'email'             => null,
                'phone'             => null,
                'position'          => null,
                'date_entry'        => null,
                'employee_status'   => null,
                'department' => [
                    'id'            => null,
                    'name'          => null
                ],
                'work_time' => [
                    'id'            => null,
                    'entry_time'    => null,
                    'home_time'     => null
                ]
            ])),
            'status'                => $this->smallInteger()->notNull()->defaultValue(2),
            'detail_info' => $this->json()->notNull()->defaultValue(json_encode([
                'change_log' => [
                    'created_at'    => null,
                    'created_by'    => null,
                    'deleted_at'    => null,
                    'deleted_by'    => null,
                    'updated_at'    => null,
                    'updated_by'    => null
                ]
            ]))
        ]);
        $this->addCommentOnColumn('{{%employee}}', 'status', '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        $this->dropCommentFromColumn('{{%employee}}', 'status');
        $this->dropTable('{{%employee}}');
    }
}
