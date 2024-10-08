<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

/**
 * This is the model class for table "absensi".
 *
 * @property int $id
 * @property string $date
 * @property bool $dayoff
 * @property int $status
 * @property string $attendance
 * @property string $blacklist
 * @property string $detail_info
 */
class Absensi extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'absensi';
    }

    public function rules()
    {
        return [
            [['date', 'attendance'], 'required'],
            [['dayoff'], 'boolean'],
            [['status'], 'integer'],
            [['date'], 'date', 'format' => 'Y-m-d'],
            [['attendance', 'blacklist', 'detail_info'], 'safe'],
            
            [['status'], 'default', 'value' => 2],
            [['dayoff'], 'default', 'value' => false],

            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
            [['status'], 'filter', 'filter' => 'intval'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['date', 'attendance', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['id', 'attendance', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'dayoff' => 'Dayoff',
            'status' => 'Status',
            'attendance' => 'Attendance',
            'blacklist' => 'Blacklist',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\AbsensiQuery(get_called_class());
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            if (isset($this->attendance)) {
                $this->attendance = Yii::$app->coreAPI->insertAttendance($this->attendance, $this);
            } 
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];
        }
        
        return parent::beforeSave($insert);
    }

    public function upsert($params, $existing)
    {
        if (isset($existing)) {
            $existing->refresh();
            $newAttendance = Yii::$app->coreAPI->insertAttendance($params['attendance'], $existing);
            $newData = array_merge($existing->attendance, $newAttendance);

            $existing->updateAttributes([
                'attendance' => $newData,
            ]);
            $existing->save();

            return $existing;
        }  

        return false;
    }

    public function updateData($params)
    {
        $attendance = $this->attendance;
        $employee_id = $params['attendance']['employee_id'];
        $end_time = $params['attendance']['end_time'];

        $attendance = Yii::$app->coreAPI->updateAttendance($attendance, $employee_id, $end_time);

        $this->attendance = $attendance;
        return $this->save();
    }

    public function getDeviceName($id)
    {
        $device = Device::findOne($id);
        return $device ? $device->name : null;
    }

    public function getWorkTimeData($id)
    {
        $workTime = WorkTime::findOne($id);

        return $workTime ? [
            'entry_time' => $workTime->entry_time,
            'home_time' => $workTime->home_time,
        ] : null;
    }
}
