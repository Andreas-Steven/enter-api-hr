<?php

namespace app\models;

use Yii;
use app\core\CoreModel;
use app\helpers\Constants;

/**
 * This is the model class for table "attendance_holidays".
 *
 * @property int $id
 * @property string $name
 * @property string $date
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 * @property int|null $sync 1: unsync, null: synced
 */
class AttendanceHoliday extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'attendance_holidays';
    }

    public function rules()
    {
        return array_merge(
            [
                [['detail_info'], 'safe'],
                [['name'], 'string', 'min' => 4, 'max' => 255],
                [['date'], 'date', 'format' => 'php:Y-m-d', 'message' => Yii::t('app', 'dateInvalid', ['label' => 'Date'])],

                [['name', 'date'], 'required'],
                
                [['sync'], 'default', 'value' => null],
                [['date'], 'unique', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE], 'when' => function($model) {
                    return $model->isAttributeChanged('date');
                }],
            ], 
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'date', 'status', 'detail_info', 'sync'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'date', 'status', 'detail_info', 'sync'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status', 'sync'];
        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'date' => 'Date',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
            'sync' => 'Sync',
        ];
    }

    public static function find()
    {
        return new \app\models\query\AttendanceHolidayQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();
        unset($fields['sync']);

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
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];
            
            return true;
        }

        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {

        }
        
        parent::afterSave($insert, $changedAttributes);
    }
}
