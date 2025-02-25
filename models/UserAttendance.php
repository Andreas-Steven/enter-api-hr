<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

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
    public $employee;
    public $work_time;

    public static function tableName()
    {
        return 'user_attendance';
    }

    public function optimisticLock() 
    {
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return array_merge(
            [
                [['detail_info'], 'safe'],
                [['work_time_id', 'employee_id'], 'integer'],

                [['work_time_id', 'employee_id'], 'filter', 'filter' => 'intval'],
                [['work_time_id', 'employee_id'], 'compare', 'compareValue' => 0, 'operator' => '>'],
                [['work_time_id', 'employee_id'], 'required' , 'on' => Constants::SCENARIO_CREATE],
                
                [['employee_id'], 'validateEmployee'],
                [['work_time_id'], 'validateWorkTime'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRules($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['employee_id', 'work_time_id', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['employee_id', 'work_time_id', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status', 'detail_info'];

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

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {

            return true;
        }

        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
                'employee' => $this->detail_info['employee'] ?? $this->employee,
                'work_time' => $this->detail_info['work_time'] ?? $this->work_time
            ];

            return true;
        }

        return false;
    }

    public function validateEmployee($attribute, $params, $validator)
    {
        $employee = Employee::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

        if ($employee === null) {
            $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->employee = ['id' => $employee->id, 'name' => $employee->name];
    }

    public function validateWorkTime($attribute, $params, $validator)
    {
        $work_time = WorkTime::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

        if ($work_time === null) {
            $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->work_time = [
            'id' => $work_time->id, 
            'name' => $work_time->name, 
            'entry_time' => $work_time->entry_time, 
            'home_time' => $work_time->home_time, 
            'working_days' => $work_time->working_days
        ];
    }
}
