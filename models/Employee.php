<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

/**
 * This is the model class for table "employee".
 *
 * @property int $id
 * @property string $name
 * @property string $employee_detail
 * @property int $status
 * @property string $detail_info
 */
class Employee extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'employee';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['employee_detail', 'detail_info'], 'safe'],
            
            [['status'], 'integer'],
            [['status'], 'default', 'value' => 2],
            [['status'], 'filter', 'filter' => 'intval'],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'employee_detail', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'employee_detail', 'detail_info', 'status'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'employee_detail' => 'Employee Detail',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\EmployeeQuery(get_called_class());
    }


    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->employee_detail = Yii::$app->coreAPI->insertEmployeeDetail($this->employee_detail, $this);
            $this->name = Yii::$app->coreAPI->htmlPurifier($this->name);
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];
            
            return true;
        }

        return false;
    }

    // Database Raelation Section
    public function getDepartment($id)
    {
        $department = Department::findOne($id);
        return [
            'department' => [
                'id' => $department->id,
                'name' => $department->name,
            ]
        ];
    }

    public function getWorkTime($id)
    {
        $workTime = WorkTime::findOne(['id' => $id]);
        return [
            'work_time' => [
                'id' => $workTime->id,
                'entry_time' => $workTime->entry_time,
                'home_time' => $workTime->home_time,
            ]
        ];
    }
}
