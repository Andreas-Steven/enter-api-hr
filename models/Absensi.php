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
            [['attendance', 'blacklist', 'detail_info'], 'safe', 'skipOnEmpty' => true],
            
            [['status'], 'default', 'value' => 2],
            [['dayoff'], 'default', 'value' => false],

            [['status'], 'filter', 'filter' => 'intval'],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['date', 'attendance', 'detail_info', 'dayoff'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['date', 'attendance', 'detail_info'];
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
        if ($this->dayoff) {
            $this->attendance = [];
        }

        if ($insert && isset($this->attendance) && !$this->dayoff) {
            $this->attendance = Yii::$app->AbsensiCoreAPI->insertAttendance($this->attendance, $this);
        }

        $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
        $this->detail_info = [
            'change_log' => $this->change_log ?: null,
        ];
        
        return parent::beforeSave($insert);
    }

    public function updateInsertAttendance($params, $existing)
    {
        if ($existing->dayoff) {
            return false;
        }

        $employee_id = $params['attendance']['employee_id'];
        $start_time = $params['attendance']['start_time'];
        $date = $params['date'];

        if (!$this->isValidStartTimeAndDate($start_time, $date)) {
            $this->addBlacklist($existing, $employee_id, $start_time, $date);
            $params['attendance']['start_time'] = date('H:i:s');
        }
        
        if (isset($existing)) {
            $existing->refresh();

            $params['attendance'] = Yii::$app->coreAPI->sanitizeEmptyFields($params['attendance']);
            $newAttendance = Yii::$app->AbsensiCoreAPI->insertAttendance($params['attendance'], $existing);
            $newData = array_merge($existing->attendance, $newAttendance);

            $existing->updateAttributes([
                'attendance' => $newData,
            ]);
            $existing->save();

            return Yii::$app->AbsensiCoreAPI->resultFormatter($existing, $newAttendance);
        }
    }

    public function updateData($params)
    {
        $attendance = $this->attendance;
        $employee_id = $params['attendance']['employee_id'];
        $end_time = $params['attendance']['end_time'];

        $attendance = Yii::$app->AbsensiCoreAPI->updateAttendance($attendance, $employee_id, $end_time);
        $this->attendance = $attendance;

        if ($this->save()) {
            $this->refresh();
            $filteredAttendance = Yii::$app->AbsensiCoreAPI->filterAttendances($this->attendance, ['employee_id' => $employee_id]);
        
            return Yii::$app->AbsensiCoreAPI->resultFormatter($this, array_values($filteredAttendance));
        }
    
        return false;
    }

    private function isValidStartTimeAndDate($start_time, $date)
    {
        $today = date('Y-m-d');
        if ($date < $today) {
            return false;
        }
    
        $previousAttendance = $this->getPreviousAttendance($date);
        if (!$previousAttendance) { 
            return true;
        }

        $filteredAttendance = Yii::$app->AbsensiCoreAPI->filterAttendances($previousAttendance->attendance, ['has_start_time' => true]);
        if (empty($filteredAttendance)) {
            return true;
        }

        $lastAttendance = end($filteredAttendance);
        if (!isset($lastAttendance['start_time'])) {
            return true;
        }

        $lastStartTime = $lastAttendance['start_time'];
        if ($start_time < $lastStartTime || $date < $previousAttendance->date) {
            return false;
        }

        return true;
    }


    public function addBlacklist($existing, $employee_id, $date, $start_time)
    {
        $blacklist = Yii::$app->AbsensiCoreAPI->insertBlacklist($existing, $employee_id, $date, $start_time);
        $existing->updateAttributes([
            'blacklist' => $blacklist,
        ]);

        return $existing->save();    
    }

    // Database Relation Section
    public function getPreviousAttendance($date)
    {
        $previousAttendance = self::find()
            ->where(['date' => $date])
            ->one();

        return $previousAttendance;
    }
    
    public function getEmployee($id)
    {
        $employee = Employee::findOne($id);
        return $employee;
    }

    public function getDevice($device_code)
    {   
        $device = Device::findOne(['device_code' => $device_code]);
        return $device;
    }
}
 