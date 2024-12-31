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
    public $work_time;
    public $employee;

    public static function tableName()
    {
        return 'user_attendance';
    }

    /**
     * {@inheritdoc}
     * @return string the column name for optimistic locking of $lockVersion.
     */
    public function optimisticLock() {
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return array_merge(
            [
                [['detail_info'], 'safe'],
                [['work_time_id', 'employee_id'], 'integer'],
                [['work_time_id', 'employee_id'], 'filter', 'filter' => 'intval'],
                [['work_time_id', 'employee_id'], 'required' , 'on' => Constants::SCENARIO_CREATE],
                [['work_time_id', 'employee_id'], 'compare', 'compareValue' => 0, 'operator' => '>'],
                [['employee_id'], 'validateEmployee'],
                [['work_time_id'], 'validateWorkTime'],
            ],
            CoreModel::getStatusRules($this),
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

    /**
     * Returns an array of fields that should be returned by default by `toArray()` when no specific fields are specified.
     * @return array the list of fields that can be exported. Defaults to all attributes of the model.
     */
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
                'employee' => $this->employee,
                'work_time' => $this->work_time

            ];

            return true;
        }

        return false;
    }

    public function validateEmployee($attribute, $params, $validator)
    {
        $employee = $this->detail_info['employee'] ?? null;

        if ($this->isAttributeChanged($attribute)) {
            $employee = Employee::find()->where([
                'id' => $this->$attribute,
                'status' => Constants::STATUS_ACTIVE
            ])->one();
        }

        if ($employee === null) {
            $this->addError($attribute, 'Employee not found');
            return;
        }

        $this->employee = ['id' => $employee['id'], 'name' => $employee['name']];
    }

    public function validateWorkTime($attribute, $params, $validator)
    {
        $work_time = $this->detail_info['work_time'] ?? null;

        if ($this->isAttributeChanged($attribute)) {
            $work_time = WorkTime::find()->where([
                'id' => $this->$attribute,
                'status' => Constants::STATUS_ACTIVE
            ])->one();
        }

        if ($work_time === null) {
            $this->addError($attribute, 'Work Time not found');
            return;
        }

        $this->work_time = ['id' => $work_time['id'], 'name' => $work_time['name'], 'entry_time' => $work_time['entry_time'], 'home_time' => $work_time['home_time'], 'working_days' => $work_time['working_days']];
    }
}
