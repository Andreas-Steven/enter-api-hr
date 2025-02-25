<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

/**
 * This is the model class for table "work_time".
 *
 * @property int $id
 * @property string $name
 * @property string $entry_time
 * @property string $home_time
 * @property int $status
 * @property string $detail_info
 */
class WorkTime extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'work_time';
    }

    public function optimisticLock() 
    {
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return array_merge(
            [
                [['name'], 'string', 'max' => 255],
                [['working_days', 'detail_info'], 'safe'],
                [['entry_time', 'home_time'], 'date', 'format' => 'php:H:i:s'],
                
                [['working_days'], 'default', 'value' => [], 'on' => Constants::SCENARIO_CREATE],
                [['name', 'entry_time', 'home_time'], 'required'],

                [['entry_time', 'home_time', 'status'], function ($attribute, $params, $validator) {
                    CoreModel::validateDependencies($attribute, $params, $validator, $this, [
                        ['className' => 'UserAttendance', 'field' => ['work_time_id']],
                    ]);
                }, 'on' => Constants::SCENARIO_UPDATE_LIST],
                
                [['working_days'], 'validateWorkingDays'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRules($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'entry_time', 'home_time', 'working_days','status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'entry_time', 'home_time', 'working_days','status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'entry_time' => 'Entry Time',
            'home_time' => 'Home Time',
            'working_days' => 'Working Days',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\WorkTimeQuery(get_called_class());
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
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];

            return true;
        }

        return false;
    }

    public function validateWorkingDays($attribute, $params) 
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, Yii::t('app', 'mustArray', ['field' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $working_days = array_filter($this->$attribute, fn($item) => !in_array($item, ['[]', '', "", null], true));

        if (empty($working_days)) {
            $this->addError($attribute, Yii::t('core', 'required', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->$attribute = array_values($working_days);
    }
}
