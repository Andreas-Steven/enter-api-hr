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
 * @property int|null $work_time_id
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
    public $work_time;
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
                [['email'], 'email'],
                [['image', 'contact', 'detail_info'], 'safe'],
                [['sso_id', 'division_id', 'status', 'work_time_id'], 'integer'],
                [['name', 'nik', 'email', 'username'], 'string', 'min' => 3, 'max' => 255],

                [['name', 'sso_id', 'username'], 'required', 'on' => Constants::SCENARIO_CREATE],
                [['work_time_id', 'division_id', 'position_id'], 'required', 'on' => Constants::SCENARIO_UPDATE],

                [['nik'], 'match', 'pattern' => '/^\d{16}$/', 'message' => Yii::t('app', 'invalidNik')],
                [['sso_id', 'division_id', 'position_id', 'employee_type_id'], 'filter', 'filter' => 'intval'],
                [['birth_date', 'date_start', 'date_resign', 'system_date'], 'match', 'pattern' => '/^\d{4}-\d{2}-\d{2}$/', 'message' => Yii::t('app', 'datePattern')],

                [['image'], function($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, 'Image');
                }],

                [['contact'], function($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, 'Contact');
                }],

                [['email'], 'unique', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE], 'when' => function($model) {
                    return $model->isAttributeChanged('email');
                }],

                [['nik'], 'unique', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE], 'when' => function($model) {
                    return $model->isAttributeChanged('nik');
                }],

                [['sso_id'], 'unique', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE], 'when' => function($model) {
                    return $model->isAttributeChanged('sso_id');
                }],

                [['image'], 'validateImage'],
                [['contact'], 'validateContact'],
                [['date_resign'], 'validateResign'],
                [['division_id'], 'validateDivision'], 
                [['position_id'], 'validatePosition'], 
                [['employee_type_id'], 'validateType'], 
                [['work_time_id'], 'validateWorkTime'],
                [['sso_id', 'username'], 'validateSSO'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRules($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['nik', 'name', 'division_id', 'position_id', 'employee_type_id', 'work_time_id', 'image', 'birth_date', 'date_start', 'date_resign', 'contact', 'email', 'username', 'sso_id', 'status','detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['nik', 'name', 'division_id', 'position_id', 'employee_type_id', 'work_time_id', 'image', 'birth_date', 'date_start', 'date_resign', 'contact', 'email', 'username', 'sso_id',  'system_date', 'status', 'detail_info'];
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
            'sso_id' => 'SSO ID',
            'division_id' => 'Division ID',
            'postion_id' => 'Postion ID',
            'employee_type_id' => 'Employee Type ID',
            'username' => 'Username',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
            'work_time_id' => 'Work Time ID',
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
            $this->name = CoreModel::htmlPurifier($this->name);

            return true;
        }

        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (empty($this->contact)) {
                $this->contact = [];
            }

            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
                'division' => $this->division,
                'position' => $this->position,
                'employee_type' => $this->employee_type,
                'work_time' => $this->work_time,
                'sso' => $this->detail_info['sso'] ?? $this->sso,
            ];

            return true;
        }

        return false;
    }

    public function validateContact($attribute, $params)
    {
        $contacts = CoreModel::ensureArray($this->$attribute);
        $contacts = array_filter($contacts, function($contact) {
            return !in_array($contact, ['[]', '', null, 'null'], true);
        });

        foreach ($contacts as $contact) {
            $contact = CoreModel::nullSafe($contact);
            if (!preg_match('/^(62)[1-9][0-9]{7,12}$/', $contact) && !empty($contact)) {
                $this->addError($attribute, Yii::t('app', 'phonePrefix'));
                return;
            }
        }

        $this->$attribute = $contacts;
    }

    public function validateImage($attribute, $params)
    {
        $array = CoreModel::ensureArray($this->$attribute);
        $array = array_filter($array, function($item) use ($attribute) {
            if (in_array($item, ['[]', '', 'null', null], true)) {
                return false;
            }

            if (!filter_var($item, FILTER_VALIDATE_URL)) {
                $this->addError($attribute, Yii::t('app', 'invalidImage', ['label' => 'Image']));
                return false;
            }

            return true;
        });
        
        $this->$attribute = array_values($array);
        
        if (sizeof($this->$attribute) > 1) {
            $this->$attribute = [$this->$attribute[0]];
        }
    }

    public function validateSSO($attribute, $params)
    {
        $this->sso = ['sso_id' => $this->sso_id, 'username' => $this->username];
    }

    public function validateDivision($attribute, $params)
    {
        $division = Division::findOne([
            'id' => $this->$attribute, 
            'status' => Constants::STATUS_ACTIVE
        ]);

        if ($division === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->division = ['id' => $division->id, 'name' => $division->name];
    }

    public function validatePosition($attribute, $params)
    {
        $position = EmployeePosition::findOne([
            'id' => $this->$attribute, 
            'status' => Constants::STATUS_ACTIVE
        ]);

        if ($position === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->position = ['id' => $position->id, 'name' => $position->name];
    }

    public function validateType($attribute, $params)
    {
        $employee_type = EmployeeType::findOne([
            'id' => $this->$attribute, 
            'status' => Constants::STATUS_ACTIVE
        ]);

        if ($employee_type === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->employee_type = ['id' => $employee_type->id, 'name' => $employee_type->name];
    }

    
    public function validateWorkTime($attribute, $params)
    {
        if (!ctype_digit((string) $this->$attribute) || intval($this->$attribute) <= 0) {
            $this->addError($attribute, Yii::t('app', 'integerNoZero', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->$attribute = intval($this->$attribute);
        $work_time = WorkTime::findOne([
            'id' => $this->$attribute, 
            'status' => Constants::STATUS_ACTIVE,
        ]);

        if ($work_time === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->work_time = [
            'id' => $work_time->id, 
            'name' => $work_time->name,
            'working_days' => $work_time->working_days,
        ];
    }

    public function validateResign($attribute, $params)
    {
        if (!empty($this->$attribute)) {
            if ($this->$attribute < $this->system_date) {
                // Status 3 for resign
                $this->status = Constants::STATUS_COMPLETED;
            } elseif ($this->$attribute > $this->system_date) {
                // Status 5 for one month notice
                $this->status = Constants::STATUS_MAINTENANCE;
            }
        }
    }
}
