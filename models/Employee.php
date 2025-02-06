<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

/**
 * This is the model class for table "employee".
 *
 * @property int $id
 * @property string $name
 * @property string $nik
 * @property string $contact
 * @property string|null $birth_date
 * @property string|null $date_start
 * @property string|null $date_resign
 * @property int $sso_id
 * @property int $division_id
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 */
class Employee extends \yii\db\ActiveRecord
{
    public $sso;
    public $division;
    public $position;
    public $username;
    public $system_date;
    public $employee_type;
    
    public static function tableName()
    {
        return 'employee';
    }

    public function optimisticLock() 
    {
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {  
        return array_merge(
            [
                [['contact', 'detail_info'], 'safe'],
                [['sso_id', 'division_id', 'status'], 'integer'],
                [['name', 'nik', 'email', 'username', 'image'], 'string', 'max' => 255],
                
                [['division_id', 'position_id', 'employee_type_id'], 'default', 'value' => 1],
                [['nik', 'iamge', 'birth_date', 'date_start', 'date_resign', 'contact', 'email'], 'default', 'value' => null],
                
                [['email', 'nik', 'sso_id'], 'unique'],
                [['email'], 'email', 'skipOnEmpty' => true],

                [['nik'], 'match', 'pattern' => '/^\d{16}$/', 'message' => Yii::t('app', 'invalidNik')],
                [['sso_id', 'division_id', 'position_id', 'employee_type_id'], 'filter', 'filter' => 'intval'],
                [['birth_date', 'date_start', 'date_resign', 'system_date'], 'date', 'format' => 'php:Y-m-d', 'skipOnEmpty' => true],
                [['nik', 'name', 'division_id', 'position_id', 'employee_type_id', 'date_start', 'sso_id', 'status'], function ($attribute, $params, $validator) {
                    CoreModel::validateDependencies($attribute, $params, $validator, $this, [
                        ['className' => 'UserAttendance', 'field' => ['employee_id']],
                    ]);
                }, 'on' => Constants::SCENARIO_UPDATE_LIST],
                
                [['contact'], 'validateContact'],
                [['date_resign'], 'validateResign'],
                [['division_id'], 'validateDivision'], 
                [['position_id'], 'validatePosition'], 
                [['employee_type_id'], 'validateType'], 
                [['sso_id', 'username'], 'validateSSO'],

            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRules($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['nik', 'name', 'username', 'division_id', 'position_id', 'employee_type_id', 'image', 'birth_date', 'date_start', 'date_resign', 'contact', 'email', 'sso_id', 'status','detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['nik', 'name', 'division_id', 'position_id', 'employee_type_id', 'image', 'birth_date', 'date_start', 'date_resign', 'system_date', 'contact', 'email', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];
        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'nik' => 'Nik',
            'contact' => 'Contact',
            'birth_date' => 'Birth Date',
            'date_start' => 'Date Start',
            'date_resign' => 'Date Resign',
            'sso_id' => 'Sso ID',
            'division_id' => 'Division ID',
            'postion_id' => 'Postion ID',
            'employee_type_id' => 'Employee Type ID',
            'username' => 'Username',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\EmployeeQuery(get_called_class());
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
            $this->name = CoreModel::htmlPurifier($this->name);
            $this->image = CoreModel::nullSafe($this->image);
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
                'division' => $this->division,
                'position' => $this->position,
                'employee_type' => $this->employee_type,
                'sso' => $this->detail_info['sso'] ?? $this->sso,
            ];

            return true;
        }

        return false;
    }


    public function validateContact($attribute, $params)
    {
        $newContact = $this->$attribute;
        $oldContact = $this->getOldAttribute($attribute);

        if (!is_array($newContact)) {
            $this->addError($attribute, Yii::t('app', 'mustArray', ['field' => $this->getAttributeLabel($attribute)]));
            return;
        }

        if (isset($newContact) && is_array($oldContact)) {
            foreach ($newContact as $index => $contact) {
                $contact = CoreModel::nullSafe($contact);

                if ($contact === null) {
                    $this->addError($attribute, Yii::t('core', 'nullField', [
                        'label' => $this->getAttributeLabel($attribute), 
                        'fields' => $this->getAttributeLabel($attribute)
                    ]));
                    return;
                }

                if (!preg_match('/^62[1-9]{1}[0-9]{7,13}$/', $contact)) {
                    $this->addError($attribute, Yii::t('app', 'phonePrefix'));
                    return;
                }

                if (array_key_exists($index, $oldContact)) {
                    if ($contact !== $oldContact[$index]) {
                        $oldContact[$index] = $contact;
                    }
                } else {
                    $oldContact[] = $contact;
                }
            }

            $this->$attribute = $oldContact;
        } else {
            $this->$attribute = array_values(array_unique($this->$attribute));
        }
    }

    public function validateResign($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            if ($this->$attribute < $this->system_date) {
                // Status 3 for resign
                $this->status = Constants::STATUS_COMPLETED;
            } else {
                // Status 5 for one month notice
                $this->status = Constants::STATUS_MAINTENANCE;
            }
        }
    }

    public function validateSSO($attribute, $params)
    {
        $this->sso = ['sso_id' => $this->sso_id, 'username' => $this->username];
    }

    public function validateDivision($attribute, $params)
    {
        $division = Division::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

        if ($division === null) {
            $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->division = ['id' => $division->id, 'name' => $division->name];
    }

    public function validatePosition($attribute, $params)
    {
        $position = EmployeePosition::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

        if ($position === null) {
            $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->position = ['id' => $position->id, 'name' => $position->name];
    }

    public function validateType($attribute, $params)
    {
        $employee_type = EmployeeType::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

        if ($employee_type === null) {
            $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->employee_type = ['id' => $employee_type->id, 'name' => $employee_type->name];
    }
}
