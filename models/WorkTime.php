<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

/**
 * This is the model class for table "work_time".
 *
 * @property int $id
 * @property string $name
 * @property string $entry_time
 * @property string $home_time
 * @property int $status
 * @property string $detail_info
 */
class WorkTime extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'work_time';
    }

    public function rules()
    {
        return array_merge(
            [
                [['name'], 'string', 'max' => 255],
                [['working_days', 'detail_info'], 'safe'],
                [['name', 'entry_time', 'home_time'], 'required'],
                [['entry_time', 'home_time'], 'date', 'format' => 'php:H:i:s'],

                [['entry_time', 'home_time', 'status'], function ($attribute, $params, $validator) {
                    CoreModel::validateDependencies($attribute, $params, $validator, $this, [
                        ['className' => 'UserAttendance', 'field' => ['work_time_id']],
                    ]);
                }, 'on' => Constants::SCENARIO_UPDATE_LIST],
            ],
            CoreModel::getStatusRules($this)
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'entry_time', 'home_time', 'working_days','status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'entry_time', 'home_time', 'working_days','status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'entry_time' => 'Entry Time',
            'home_time' => 'Home Time',
            'working_days' => 'Working Days',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\WorkTimeQuery(get_called_class());
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->name = CoreModel::htmlPurifier($this->name);
            $this->change_log = CoreModel::getChangeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log,
            ];

            return true;
        }

        return false;
    }
}
