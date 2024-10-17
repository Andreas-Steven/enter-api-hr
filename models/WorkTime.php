<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

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
    public $change_log;

    public static function tableName()
    {
        return 'work_time';
    }

    public function rules()
    {
        return [
            [['entry_time', 'home_time'], 'required'],
            [['entry_time', 'home_time'], 'date', 'format' => 'php:H:i:s'],

            [['status'], 'integer'],
            [['detail_info'], 'safe'],

            [['status'], 'default', 'value' => 2],
            [['status'], 'filter', 'filter' => 'intval'],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['entry_time', 'home_time'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['entry_time', 'home_time'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'entry_time' => 'Entry Time',
            'home_time' => 'Home Time',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\WorkTimeQuery(get_called_class());
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];

            return true;
        }

        return false;
    }
}
