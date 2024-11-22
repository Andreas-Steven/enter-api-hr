<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

/**
 * This is the model class for table "user_attendance".
 *
 * @property int $id
 * @property int $work_time_id
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 */
class UserAttendance extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'user_attendance';
    }

    public function rules()
    {
        return array_merge(
            [
                [['detail_info'], 'safe'],
                [['work_time_id', 'employee_id'], 'integer'],
                [['work_time_id', 'employee_id'], 'required' , 'on' => Constants::SCENARIO_CREATE],
            ],
            Yii::$app->coreAPI->statusRules($this)
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'status', 'detail_info', 'sync'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'status', 'detail_info', 'sync'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status', 'sync'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        
        return [
            'id' => 'ID',
            'work_time_id' => 'Work Time ID',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\UserAttendanceQuery(get_called_class());
    }
}
