<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

/**
 * This is the model class for table "base_salary".
 *
 * @property int $id
 * @property int $employee_id
 * @property float $base_salary
 * @property string $efective_date
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 */
class BaseSalary extends \yii\db\ActiveRecord
{
    public $employee;

    public static function tableName()
    {
        return 'base_salary';
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
                [['base_salary'], 'number'],
                [['employee_id'], 'integer'],

                [['employee_id'], 'filter', 'filter' => 'intval'],
                [['base_salary'], 'filter', 'filter' => 'floatval'],
                [['efective_date'], 'date' , 'format' => 'php:Y-m-d'],
                [['employee_id', 'base_salary', 'efective_date'], 'required'],

                [['employee_id'], 'validateEmployee'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRules($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['employee_id', 'base_salary', 'efective_date', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['employee_id', 'base_salary', 'efective_date', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'employee_id' => 'Employee ID',
            'base_salary' => 'Base Salary',
            'efective_date' => 'Efective Date',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\BaseSalaryQuery(get_called_class());
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
            $this->base_salary = floatval($this->base_salary);
            $this->detail_info = [
                'employee' => $this->employee,
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];

            return true;
        }

        return false;
    }

    public function validateEmployee($attribute, $params, $validator)
    {
        $employee = Employee::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

        if ($employee === null) {
            $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->employee = ['id' => $employee->id, 'name' => $employee->name];
    }
}
