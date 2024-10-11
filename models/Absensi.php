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
        $scenarios[Constants::SCENARIO_CREATE] = ['date', 'attendance', 'detail_info', 'dayoff'];
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

    // Main Function Section
    public function beforeSave($insert)
    {
        if ($this->dayoff) {
            $this->attendance = [];
        }

        if ($insert && isset($this->attendance) && !$this->dayoff) {
            $this->attendance = Yii::$app->coreAPI->insertAttendance($this->attendance, $this);
        }

        $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
        $this->detail_info = [
            'change_log' => $this->change_log ?: null,
        ];
        
        return parent::beforeSave($insert);
    }

    public function updateInsertAttendance($params, $existing)
    {
        if (!$existing->dayoff) {
            $start_time = $params['attendance']['start_time'];
            $date = $params['date'];
            $employee_id = $params['attendance']['employee_id'];
    
            if (!$this->isValidStartTimeAndDate($start_time, $date)) {
                $this->addBlacklist($existing, $employee_id, $start_time, $date);
                return $existing;
            }
            
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

        $lastIndex = count($previousAttendance->attendance) - 1;
        $lastStartTime = $previousAttendance->attendance[$lastIndex]['start_time'];
        if ($start_time < $lastStartTime || $date < $previousAttendance->date) {
            return false;
        }

        return true;
    }

    private function getPreviousAttendance($date)
    {
        $previousAttendance = Absensi::find()
            ->where(['date' => $date])
            ->one();

        return $previousAttendance;
    }

    public function addBlacklist($existing, $employee_id, $date, $start_time)
    {
        $blacklist = Yii::$app->coreAPI->insertBlacklist($existing, $employee_id, $date, $start_time);
        $existing->updateAttributes([
            'blacklist' => $blacklist,
        ]);

        return $existing->save();    
    }

    // Database Relation Section
    public function getDeviceName($id)
    {
        $device = Device::findOne($id);
        return $device ? $device->name : null;
    }

    public function getDepartmentName($id)
    {
        $department = Department::findOne($id);
        return $department ? $department->name : null;
    }

    public function getWorkTimeData($id)
    {
        $workTime = WorkTime::findOne($id);
        return $workTime ? [
            'entry_time' => $workTime->entry_time,
            'home_time' => $workTime->home_time,
        ] : null;
    }

    public function getEmployeeName($id)
    {
        $employee = Employee::findOne($id);
        return $employee ? $employee->name : null;
    }
}
 