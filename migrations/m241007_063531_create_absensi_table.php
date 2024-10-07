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
            'status' => $this->smallInteger()->notNull()->defaultValue(2),
            'attendance' => $this->json()->notNull()->defaultValue(json_encode([
                [
                    'employee_id'   => null,
                    'start_time'    => null,
                    'end_time'      => null,
                    'info' => [
                        'in'        => false,
                        'out'       => false,
                        'detail'    => []
                    ],
                    'device' => [
                        'id'        => null,
                        'name'      => null
                    ],
                    'work_time' => [
                        'id'        => null,
                        'end_time'  => null,
                        'home_time' => null
                    ],
                    'location' => [
                        'lat'       => null,
                        'long'      => null,
                        'image'     => []
                    ]
                ]
            ])),
            'blacklist' => $this->json()->notNull()->defaultValue(json_encode([
                'employee_id'   => null,
                'date_time'     => null
            ])),
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
        $this->dropTable('{{%absensi}}');
    }
}
