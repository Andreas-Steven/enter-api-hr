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
    public $system_date;
    public $employee_type;
    public $work_time;
    
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
                [['image', 'contact', 'detail_info'], 'safe'],
                [['sso_id', 'division_id', 'status', 'work_time_id'], 'integer'],
                [['name', 'nik', 'email', 'username'], 'string', 'min' => 3, 'max' => 255],
                
                [['image', 'contact'], 'default', 'value' => [], 'on' => Constants::SCENARIO_CREATE],
                [['division_id', 'position_id', 'employee_type_id'], 'default', 'value' => 1, 'on' => Constants::SCENARIO_CREATE],
                [['nik', 'birth_date', 'date_start', 'date_resign', 'email', 'work_time_id'], 'default', 'value' => null, 'on' => Constants::SCENARIO_CREATE],
                
                [['email'], 'email', 'on' => Constants::SCENARIO_UPDATE],
                [['email'], 'unique', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE], 'when' => function($model) {
                    return $model->isAttributeChanged('email');
                }],
                [['nik'], 'unique', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE], 'when' => function($model) {
                    return $model->isAttributeChanged('nik');
                }],
                [['sso_id'], 'unique', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE], 'when' => function($model) {
                    return $model->isAttributeChanged('sso_id');
                }],

                [['name', 'sso_id', 'username'], 'required', 'on' => Constants::SCENARIO_CREATE],
                [['work_time_id', 'division_id', 'position_id'], 'required', 'on' => Constants::SCENARIO_UPDATE],

                [['nik'], 'match', 'pattern' => '/^\d{16}$/', 'message' => Yii::t('app', 'invalidNik')],
                [['sso_id', 'division_id', 'position_id', 'employee_type_id', 'work_time_id'], 'filter', 'filter' => 'intval'],
                [['birth_date', 'date_start', 'date_resign', 'system_date'], 'match', 'pattern' => '/^\d{4}-\d{2}-\d{2}$/', Yii::t('app', 'datePattern')],

                [['image'], 'validateImage'],
                [['contact'], 'validateContact'],
                [['date_resign'], 'validateResign'],
                [['division_id'], 'validateDivision'], 
                [['position_id'], 'validatePosition'], 
                [['employee_type_id'], 'validateType'], 
                [['sso_id', 'username'], 'validateSSO'],
                [['work_time_id'], 'validateWorkTime'],
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

            if ($this->scenario === Constants::SCENARIO_UPDATE) {
                if (empty($this->work_time_id)) {
                    $this->addError('work_time_id', Yii::t('app', 'required', ['label' => $this->getAttributeLabel('work_time_id')]));
                    return;
                }
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
        $contacts = $this->$attribute;
    
        if (!is_array($contacts)) {
            $this->addError($attribute, Yii::t('app', 'array', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }
    
        $contacts = array_filter($contacts, function($contact) {
            return !in_array($contact, ['[]', '', null], true);
        });

        foreach ($contacts as $contact) {
            $contact = CoreModel::nullSafe($contact);

            if ($contact === null) {
                $this->addError($attribute, Yii::t('app', 'null', [
                    'label' => $this->getAttributeLabel($attribute), 
                ]));
                return;
            }

            if (!preg_match('/^(62)[1-9][0-9]{7,12}$/', $contact)) {
                $this->addError($attribute, Yii::t('app', 'phonePrefix'));
                return;
            }
        }

        $this->$attribute = array_values(array_unique($contacts));
    }

    public function validateImage($attribute, $params)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, Yii::t('app', 'array', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->$attribute = array_filter($this->$attribute, function($item) {
            if (!in_array($item, ['[]', '', null], true)) {
                if (!filter_var($item, FILTER_VALIDATE_URL)) {
                    $this->addError('image', Yii::t('app', 'invalidImage', ['label' => 'Image']));
                    return false;
                }
                return true;
            }
            return false;
        });

        $this->$attribute = array_values($this->$attribute);

        if (sizeof($this->$attribute) > 1) {
            $this->$attribute =  [$this->$attribute[0]];
        }
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
        $work_time = WorkTime::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

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
}
