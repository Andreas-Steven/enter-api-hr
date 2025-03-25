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
                
                [['name', 'working_days'], 'required'],
                [['working_days'], 'default', 'value' => [], 'on' => Constants::SCENARIO_CREATE],

                [['name', 'working_days', 'status'], function ($attribute, $params, $validator) {
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
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'working_days', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'working_days', 'status', 'detail_info'];
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
        $workingDays = $this->$attribute;

        if (!is_array($workingDays)) {
            $this->addError($attribute, Yii::t('app', 'mustArray', ['field' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $allowedFields = Yii::$app->params['allowedFields']['working_days'];
        CoreModel::validateRequiredFields($this, $attribute, $allowedFields, $this->$attribute);
        
        $allowedFields = Yii::$app->params['allowedFields']['time_structure'];
        foreach ($workingDays as $dayName => $times) {
            CoreModel::validateRequiredFields($this, "{$attribute}[$dayName]", $allowedFields, $times);
        }

        array_walk($workingDays, function (&$times, $dayName) use ($attribute) {
            $entry_time = CoreModel::nullSafe($times['entry_time']);
            $home_time = CoreModel::nullSafe($times['home_time']);

            if ($entry_time !== null) {
                $validEntryTime = \DateTime::createFromFormat('H:i:s', $entry_time);
                if (!$validEntryTime || $validEntryTime->format('H:i:s') !== $entry_time) {
                    $this->addError($attribute . "[$dayName][entry_time]", Yii::t('app', 'invalidTimeFormat', ['field' => $this->getAttributeLabel($attribute . "[$dayName][entry_time]")]));
                }
            }

            if ($home_time !== null) {
                $validHomeTime = \DateTime::createFromFormat('H:i:s', $home_time);
                if (!$validHomeTime || $validHomeTime->format('H:i:s') !== $home_time) {
                    $this->addError($attribute . "[$dayName][home_time]", Yii::t('app', 'invalidTimeFormat', ['field' => $this->getAttributeLabel($attribute . "[$dayName][home_time]")]));
                }
            }

            $times['entry_time'] = $entry_time;
            $times['home_time'] = $home_time;
        });

        $this->$attribute = $workingDays;
    }
}
