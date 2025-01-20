<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

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
    public static function tableName()
    {
        return 'payroll';
    }

    public function optimisticLock() 
    {
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return array_merge(
            [
                [['pay_period'], 'string', 'max' => 255],
                [['salary_detail', 'detail_info'], 'safe'],
                [['pay_date'], 'date', 'format' => 'php:Y-m-d'],
                [['pay_period', 'salary_detail'], 'required', 'on' => Constants::SCENARIO_CREATE],

                [['salary_detail'], 'validateSalaryDetail'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRules($this),
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
            $this->pay_period = CoreModel::htmlPurifier($this->pay_period);
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];
            return true;
        }
        return false;
    }

    public function validateSalaryDetail($attribute, $params, $validator)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, Yii::t('app', 'mustArray', ['field' => $this->getAttributeLabel('Salary')]));
            return;
        }

        $salaryDetail = [];
        $salaryDetailData = $this->$attribute;
        $oldSalaryDetail = $this->getOldAttribute($attribute);

        foreach ($salaryDetailData as $key => $value) {
            if (isset($oldSalaryDetail[$value['employee_id']])) {
                $salaryDetail = array_merge($oldSalaryDetail[$value['employee_id']], $value);
                $salaryDetail['deductions'] = array_merge(Yii::$app->params['defaultValue']['salary_detail']['deductions'], $oldSalaryDetail[$value['employee_id']]['deductions']);
                $salaryDetail['earnings'] = array_merge(Yii::$app->params['defaultValue']['salary_detail']['earnings'], $oldSalaryDetail[$value['employee_id']]['earnings']);
                $salaryDetail['absences'] = array_merge(Yii::$app->params['defaultValue']['salary_detail']['absences'], $oldSalaryDetail[$value['employee_id']]['absences']);
            } else {
                $salaryDetail = array_merge(Yii::$app->params['defaultValue']['salary_detail'], $value);
                $salaryDetail['deductions'] = array_merge(Yii::$app->params['defaultValue']['salary_detail']['deductions'], $salaryDetail['deductions']);
                $salaryDetail['earnings'] = array_merge(Yii::$app->params['defaultValue']['salary_detail']['earnings'], $salaryDetail['earnings']);
                $salaryDetail['absences'] = array_merge(Yii::$app->params['defaultValue']['salary_detail']['absences'], $salaryDetail['absences']);
            }

            $salaryDetail['employee_id'] = intval($salaryDetail['employee_id']);
            $employee = Employee::findOne(['id' => $salaryDetail['employee_id'], 'status' => Constants::STATUS_ACTIVE]);
            if ($employee === null) {
                $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('Employee')]));
                return;
            }

            $baseSalary = BaseSalary::findOne(['employee_id' => $salaryDetail['employee_id'], 'status' => Constants::STATUS_ACTIVE]);
            if ($baseSalary === null) {
                $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('BaseSalary')]));
                return;
            }

            $convertAndValidateFloat = function(&$item, $key) use ($attribute) {
                if (!is_numeric($item) || floatval($item) < 0) {
                    $this->addError($attribute, Yii::t('core', 'float', ['field' => $this->getAttributeLabel($key)]));
                    return;
                } else {
                    $item = floatval($item);
                }
            };

            $convertAndValidateInt = function(&$item, $key) use ($attribute) {
                if ($item !== null) {
                    if (!is_numeric($item) || intval($item) < 0) {
                        $this->addError($attribute, Yii::t('core', 'integer', ['field' => $this->getAttributeLabel($key)]));
                        return;
                    } else {
                        $item = intval($item);
                    }
                }
            };
            
            $division = Division::findOne(['id' => $employee->division_id, 'status' => Constants::STATUS_ACTIVE]);
            if ($division === null) {
                $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('Division')]));
                return;
            }
    
            array_walk($salaryDetail['earnings'], $convertAndValidateFloat);
            array_walk($salaryDetail['deductions'], $convertAndValidateFloat);
            array_walk($salaryDetail['absences'], $convertAndValidateInt);
            $salaryDetail['summary']['base_salary'] = floatval($baseSalary->base_salary);
            $salaryDetail['summary'] = array_map('floatval', $salaryDetail['summary']);

            $total_deductions = array_sum($salaryDetail['deductions']);
            $total_earnings = array_sum($salaryDetail['earnings']);
            $gross_salary = $salaryDetail['summary']['base_salary'] + $total_earnings;
            $net_salary = $gross_salary - $total_deductions;
            $salaryDetail['employee_name'] = $employee->name;
            $salaryDetail['division'] = [
                'id' => $employee->division_id,
                'name' => $division->name
            ];
            $salaryDetail['summary'] = [
                "base_salary" => $salaryDetail['summary']['base_salary'],
                "total_earnings" => $total_earnings,
                "gross_salary" => $gross_salary,
                "total_deductions" => $total_deductions,
                "net_salary" => $net_salary,
            ];

            $oldSalaryDetail[$key] = $salaryDetail;
        }

        $this->$attribute = $oldSalaryDetail;
    }
}