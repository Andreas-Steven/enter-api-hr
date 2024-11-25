<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

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
    
    public static function tableName()
    {
        return 'employee';
    }

    public function rules()
    {  
        return array_merge(
            [
                [['contact', 'detail_info'], 'safe'],
                [['name', 'nik'], 'string', 'max' => 255],
                [['sso_id', 'division_id', 'status'], 'integer'],

                [['nik', 'sso_id'], 'unique'],
                [['nik', 'name', 'sso_id', 'division_id'], 'required'],
                [['contact'], 'validateContact', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE]],

                [['birth_date', 'date_start', 'date_resign', 'contact'], 'filter', 'skipOnEmpty' => true, 'filter' => function($value) {
                    return $value === "" ? null : $value; }],
                [['birth_date', 'date_start', 'date_resign'], 'date', 'format' => 'php:Y-m-d', 'when' => function($model, $attribute) { 
                    return $model->$attribute !== null && isset($model->$attribute); }],
            ],
            Yii::$app->coreAPI->statusRules($this)
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['nik', 'name', 'sso_id', 'division_id', 'birth_date', 'status', 'contact', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['nik', 'name', 'sso_id', 'division_id', 'birth_date', 'date_start', 'date_resign', 'status', 'contact', 'detail_info'];
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
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\EmployeeQuery(get_called_class());
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

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->name = Yii::$app->coreAPI->htmlPurifier($this->name);
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];

            return true;
        }

        return false;
    }
}
