<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

/**
 * This is the model class for table "payroll".
 *
 * @property int $id
 * @property string $pay_period
 * @property string|null $pay_date
 * @property string $salary_detail
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 */
class Payroll extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'payroll';
    }

    public function rules()
    {
        return array_merge(
            [
                [['pay_period'], 'string', 'max' => 255],
                [['salary_detail', 'detail_info'], 'safe'],
                [['pay_date'], 'date', 'format' => 'php:Y-m-d'],
                [['pay_period', 'pay_date', 'salary_detail'], 'required', 'on' => Constants::SCENARIO_CREATE],

                [['salary_detail'], 'validateSalaryDetail'],
            ],
            Yii::$app->coreAPI->statusRules($this)
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['pay_period', 'pay_date', 'salary_detail', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['pay_period', 'pay_date', 'salary_detail', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pay_period' => 'Pay Period',
            'pay_date' => 'Pay Date',
            'salary_detail' => 'Salary Detail',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\PayrollQuery(get_called_class());
    }

    public function validateSalaryDetail($attribute, $params, $validator)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, 'Attendance must be an array');
            return;
        }

        $salaryDetail = [];
        $salaryDetailData = $this->$attribute;
        $oldSalaryDetail = $this->getOldAttribute($attribute);

        foreach ($salaryDetailData as $key => $value) {
            if (isset($oldSalaryDetail[$value['employee_id']])) {
                $salaryDetail = array_merge($oldSalaryDetail[$value['employee_id']], $value);
            } else {
                $salaryDetail = array_merge(Yii::$app->params['salary_detail'], $value);
                $salaryDetail['deductions'] = array_merge(Yii::$app->params['salary_detail']['deductions'], $salaryDetail['deductions']);
            }

            $salaryDetail['employee_id'] = intval($salaryDetail['employee_id']);
            $employee = Employee::find()->where(['id' => $salaryDetail['employee_id'], 'status' => Constants::STATUS_ACTIVE])->one();
            if ($employee === null) {
                $this->addError($attribute.'['.$key.'].employee_id', 'Employee not found');
                return;
            }

            $baseSalary = BaseSalary::find()->where(['employee_id' => $salaryDetail['employee_id'], 'status' => Constants::STATUS_ACTIVE])->one();
            if ($baseSalary === null) {
                $this->addError($attribute.'['.$key.'].employee_id.base_salary', 'Base salary not found');
                return;
            }

            $salaryDetail['overtime_pay'] = floatval($salaryDetail['overtime_pay']);
            if ($salaryDetail['overtime_pay']  < 0) {
                $this->addError($attribute.'['.$key.'].overtime_pay', 'Price value must be a float and greater than or equal to 0');
                return;
            } 

            $salaryDetail['deductions']['tax'] = floatval($salaryDetail['deductions']['tax']);
            if ($salaryDetail['deductions']['tax']  < 0) {
                $this->addError($attribute.'['.$key.'].deductions.tax', 'Price value must be a float and greater than or equal to 0');
                return;
            } 

            $salaryDetail['deductions']['loan'] = floatval($salaryDetail['deductions']['loan']);
            if ($salaryDetail['deductions']['loan']  < 0) {
                $this->addError($attribute.'['.$key.'].deductions.loan', 'Price value must be a float and greater than or equal to 0');
                return;
            } 

            $salaryDetail['deductions']['absent'] = floatval($salaryDetail['deductions']['absent']);
            if ($salaryDetail['deductions']['absent']  < 0) {
                $this->addError($attribute.'['.$key.'].deductions.absent', 'Price value must be a float and greater than or equal to 0');
                return;
            } 

            $salaryDetail['deductions']['early_leave'] = floatval($salaryDetail['deductions']['early_leave']);
            if ($salaryDetail['deductions']['early_leave']  < 0) {
                $this->addError($attribute.'['.$key.'].deductions.early_leave', 'Price value must be a float and greater than or equal to 0');
                return;
            } 

            $salaryDetail['deductions']['late'] = floatval($salaryDetail['deductions']['late']);
            if ($salaryDetail['deductions']['late']  < 0) {
                $this->addError($attribute.'['.$key.'].deductions.late', 'Price value must be a float and greater than or equal to 0');
                return;
            } 

            $salaryDetail['bonuses'] = floatval($salaryDetail['bonuses']);
            if ($salaryDetail['bonuses'] < 0) {
                $this->addError($attribute.'['.$key.'].bonuses', 'Price value must be a float and greater than or equal to 0');
                return;
            } 

            $salaryDetail['employee_name'] = $employee->name;
            $salaryDetail['base_salary'] = floatval($baseSalary->base_salary);
            $salaryDetail['gross_salary'] = floatval($salaryDetail['base_salary'] + $salaryDetail['bonuses'] + $salaryDetail['overtime_pay']);
            $totalDeduction = $salaryDetail['deductions']['tax'] + $salaryDetail['deductions']['loan'] + $salaryDetail['deductions']['absent'] + $salaryDetail['deductions']['early_leave'] + $salaryDetail['deductions']['late'];
            $salaryDetail['net_salary'] = floatval($salaryDetail['gross_salary'] - $totalDeduction);

            $oldSalaryDetail[$key] = $salaryDetail;
        }

        $this->$attribute = $oldSalaryDetail;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->pay_period = Yii::$app->coreAPI->htmlPurifier($this->pay_period);

            $this->detail_info = [
                'change_log' => $this->change_log,
            ];
            return true;
        }
        return false;
    }
}
