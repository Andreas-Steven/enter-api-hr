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
    public $system_date;
    public $division;
    public $position;
    public $employee_type;
    public $username;
    public $sso;
    
    public static function tableName()
    {
        return 'employee';
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
                [['nik', 'sso_id'], 'unique'],
                [['contact', 'detail_info'], 'safe'],
                [['sso_id', 'division_id', 'status'], 'integer'],
                [['name', 'nik', 'email', 'image'], 'string', 'max' => 255],
                [['sso_id', 'division_id', 'position_id', 'employee_type_id'], 'filter', 'filter' => 'intval'],

                [['division_id', 'position_id', 'employee_type_id'], 'default', 'value' => 1],
                [['nik', 'iamge', 'birth_date', 'date_start', 'date_resign', 'contact', 'email'], 'default', 'value' => null],

                [['birth_date', 'date_start', 'date_resign', 'contact'], 'filter', 'skipOnEmpty' => true, 'filter' => function($value) {
                    return $value === "" ? null : $value; }
                ],
                [['birth_date', 'date_start', 'date_resign', 'system_date'], 'date', 'format' => 'php:Y-m-d', 'when' => function($model, $attribute) { 
                    return $model->$attribute !== null && isset($model->$attribute); }
                ],

                [['nik', 'name', 'division_id', 'position_id', 'employee_type_id', 'image', 'birth_date', 'date_start', 'date_resign', 'contact', 'email', 'sso_id', 'status'], function ($attribute, $params, $validator) {
                    CoreModel::validateDependencies($attribute, $params, $validator, $this, [
                        ['className' => 'UserAttendance', 'field' => ['employee_id']],
                        // ['className' => 'BaseSalary', 'field' => ['employee_id']],
                    ]);
                }, 'on' => Constants::SCENARIO_UPDATE_LIST],
                
                [['contact'], 'validateContact'],
                [['division_id'], 'validateDivision'], 
                [['position_id'], 'validatePosition'], 
                [['sso_id', 'username'], 'validateSSO'],
                [['date_resign'], 'validateResignDate'],
                [['employee_type_id'], 'validateEmployeeType'], 
            ],
            CoreModel::getStatusRules($this),
            // CoreModel::getLockVersionRules($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['nik', 'name', 'username', 'division_id', 'position_id', 'employee_type_id', 'image', 'birth_date', 'date_start', 'date_resign', 'contact', 'email', 'sso_id', 'status','detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['nik', 'name', 'division_id', 'position_id', 'employee_type_id', 'image', 'birth_date', 'date_start', 'date_resign', 'contact', 'email', 'status', 'detail_info'];
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
            $this->name = CoreModel::htmlPurifier($this->name);
            $this->image = CoreModel::nullSafe($this->image);
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
                'sso' => $this->sso !== null ? $this->sso : $this->detail_info['sso'],
                'division' => $this->division,
                'position' => $this->position,
                'employee_type' => $this->employee_type
            ];

            return true;
        }

        return false;
    }

    public function validateSSO($attribute, $params, $validator)
    {
        if (isset($this->detail_info['sso'])) {
            $this->sso = $this->detail_info['sso'];
        } else {
            $this->sso = ['sso_id' => $this->sso_id, 'username' => $this->username];
        }
    }

    public function validateDivision($attribute, $params, $validator)
    {
        if ($this->isAttributeChanged($attribute)) {
            $division = null;
            $this->$attribute = ($this->$attribute === 0) ? 1 : $this->$attribute;

            if ($this->isAttributeChanged($attribute)) {
                $division = Division::find()->where([
                    'id' => $this->$attribute,
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
            }

            if ($division === null) {
                $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('Division')]));
                return;
            }
            
        } else { 
            if (isset($this->detail_info['division'])) {
                $division = $this->detail_info['division'];
            }
        }
        
        $this->division = ['id' => $division['id'], 'name' => $division['name']];
    }

    public function validatePosition($attribute, $params, $validator)
    {
        if ($this->isAttributeChanged($attribute)) {
            $position = null;
            $this->$attribute = ($this->$attribute === 0) ? 1 : $this->$attribute;

            if ($this->isAttributeChanged($attribute)) {
                $position = EmployeePosition::find()->where([
                    'id' => $this->$attribute,
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
            }

            if ($position === null) {
                $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('EmployeePosition')]));
                return;
            }
            
        } else { 
            if (isset($this->detail_info['position'])) {
                $position = $this->detail_info['position'];
            }
        }
        
        $this->position = ['id' => $position['id'], 'name' => $position['name']];
    }

    public function validateEmployeeType($attribute, $params, $validator)
    {
        if ($this->isAttributeChanged($attribute)) {
            $employee_type = null;
            $this->$attribute = ($this->$attribute === 0) ? 1 : $this->$attribute;

            if ($this->isAttributeChanged($attribute)) {
                $employee_type = EmployeeType::find()->where([
                    'id' => $this->$attribute,
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
            }

            if ($employee_type === null) {
                $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('EmployeeType')]));
                return;
            }
            
        } else { 
            if (isset($this->detail_info['employee_type'])) {
                $employee_type = $this->detail_info['employee_type'];
            }
        }
        
        $this->employee_type = ['id' => $employee_type['id'], 'name' => $employee_type['name']];
    }

    public function validateContact($attribute, $params, $validator)
    {
        $oldContact = $this->getOldAttribute($attribute);

        if ($this->$attribute !== null && is_array($this->$attribute)) {
            if (is_array($oldContact)) {
                foreach ($this->$attribute as $index => $newContact) {
                    $newContact = CoreModel::nullSafe($newContact);
                    if ($newContact === null) {
                        $this->addError($attribute, Yii::t('core', 'nullField', ['label' => $this->getAttributeLabel('contact'), 'fields' => $this->getAttributeLabel('Contact')]));
                        return;
                    }

                    if (!str_starts_with($newContact, Yii::$app->params['prefix']['phone_number'])) {
                        $this->addError($attribute, Yii::t('app', 'phonePrefix'));
                        return;
                    }

                    if (array_key_exists($index, $oldContact)) {
                        if ($newContact != $oldContact[$index]) {
                            $oldContact[$index] = $newContact;
                        }
                    } else {
                        
                        $oldContact[] = $newContact;
                    }
                }
                $this->$attribute = $oldContact;
            } else {
                $this->$attribute = array_values(array_unique($this->$attribute));
            }
        }
    }

    public function validateResignDate($attribute, $params, $validator)
    {
        // Status 5 for one month notice
        if (!empty($this->date_resign) ) {
            $this->status = Constants::STATUS_MAINTENANCE;
        }

        // Status 3 for resign
        if ($this->$attribute < $this->system_date) {
            $this->status = Constants::STATUS_COMPLETED;
        }
    }
}
