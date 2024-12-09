<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\models\CoreModel;

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
    public $change_log;
    public $system_date;
    public $division;
    public $username;
    public $sso;
    
    public static function tableName()
    {
        return 'employee';
    }

    public function rules()
    {  
        return array_merge(
            [
                [['contact', 'detail_info'], 'safe'],
                [['name', 'nik', 'image'], 'string', 'max' => 255],
                [['sso_id', 'division_id', 'status'], 'integer'],

                [['nik', 'sso_id'], 'unique'],
                [['sso_id', 'division_id'], 'filter', 'filter' => 'intval'],
                [['division_id'], 'default', 'value' => 1],

                [['birth_date', 'date_start', 'date_resign', 'contact'], 'filter', 'skipOnEmpty' => true, 'filter' => function($value) {
                    return $value === "" ? null : $value; }],
                [['birth_date', 'date_start', 'date_resign', 'system_date'], 'date', 'format' => 'php:Y-m-d', 'when' => function($model, $attribute) { 
                    return $model->$attribute !== null && isset($model->$attribute); }],
                
                [['contact'], 'validateContact'],
                [['sso_id', 'username'], 'validateSSO'],
                [['division_id'], 'validateDivision'], 
                [['date_resign'], 'validateResignDate'],
            ],
            CoreModel::getStatusRules($this)
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'sso_id', 'username', 'division_id', 'status','detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['nik', 'name', 'division_id', 'image', 'birth_date', 'date_start', 'date_resign', 'system_date', 'status', 'contact', 'detail_info'];
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
            'username' => 'Username',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\EmployeeQuery(get_called_class());
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
                $this->addError($attribute, 'Division data not found');
                return;
            }
            
        } else { 
            if (isset($this->detail_info['division'])) {
                $division = $this->detail_info['division'];
            }
        }
        
        $this->division = ['id' => $division['id'], 'name' => $division['name']];
    }

    public function validateContact($attribute, $params, $validator)
    {
        $oldContact = $this->getOldAttribute($attribute);

        if ($this->$attribute !== null && is_array($this->$attribute)) {
            if (is_array($oldContact)) {
                foreach ($this->$attribute as $index => $newContact) {
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

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->name = CoreModel::htmlPurifier($this->name);
            $this->change_log = CoreModel::getChangeLog($this, $insert);
            
            $this->detail_info = [
                'change_log' => $this->change_log,
                'division' => $this->division,
                'sso' => $this->sso !== null ? $this->sso : $this->detail_info['sso']
            ];

            return true;
        }

        return false;
    }
}
