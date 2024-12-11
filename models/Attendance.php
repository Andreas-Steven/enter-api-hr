<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\models\CoreModel;
use app\models\Device;
use app\models\Employee;
use app\models\Division;
use app\models\WorkTime;
use app\models\UserAttendance;

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
class Attendance extends \yii\db\ActiveRecord
{
    public $change_log;
    public $employee_id;
    public $start_time;
    public $end_time;
    public $employee_name;
    public $division;
    public $work_time;
    public $device;
    public $device_code;
    public $system_date;
    public $dateMismatch;

    public static function tableName()
    {
        return 'attendance';
    }

    public function rules()
    {
        return array_merge(
            [
                [['date'], 'required'],
                [['date'], 'filter', 'filter' => function($value) {
                    return CoreModel::nullSafe($value);
                }],
                [['employee_id'], 'filter', 'filter' => 'intval'],
                [['attendance', 'blacklist', 'detail_info'], 'safe'],
                [['end_time', 'start_time'], 'date', 'format' => 'php:H:i:s'],
                [['date', 'system_date', 'dateMismatch'], 'date', 'format' => 'php:Y-m-d'],
                
                [['attendance'], 'validateAttendance', 'on' => Constants::SCENARIO_CREATE],
                [['attendance'], 'validateAttendanceNotCheckout', 'on' => Constants::SCENARIO_UPDATE],
            ],
            CoreModel::getStatusRules($this)
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['date', 'system_date', 'dateMismatch', 'attendance', 'dateMismatch', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['attendance', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'status' => 'Status',
            'attendance' => 'Attendance',
            'blacklist' => 'Blacklist',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\AttendanceQuery(get_called_class());
    }

    public function validateAttendance($attribute, $params)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, 'Attendance must be an array');
            return;
        }

        $employeeAttendance = [];
        $attendanceData = $this->$attribute;
        $oldAttendance = $this->getOldAttribute($attribute);
        $lastStartTime = $oldAttendance ? array_column($oldAttendance, 'start_time') : [];
        $latestAttendance = !empty($lastStartTime) ? end($lastStartTime) : null;

        foreach ($attendanceData as $key => $value) {
            $employeeAttendance = array_merge(Yii::$app->params['attendance'], $value);
            if (empty($employeeAttendance['employee_id'])) {
                $this->addError($attribute, 'Employee Id must be filled');
                return;
            }
            
            if (empty($employeeAttendance['start_time']) && empty($employeeAttendance['end_time'])) {
                $this->addError($attribute, 'Start time or end time must be filled');
                return;
            }
            
            if (isset($value['end_time'])) {
                if (!empty($value['end_time']) && strtotime($value['end_time']) === false) {
                    $this->addError($attribute, 'Attendance end time value not valid');
                    return;
                }
            }
            
            if (!isset($employeeAttendance['device']['device_code'])) {
                $employeeAttendance['device']['device_code'] = Constants::DEVICE_APP;
                if (empty($value['location'])) {
                    $this->addError($attribute.'['.$key.'].location', 'Location must be set for device app');
                    return;
                }
            }
            
            if ($employeeAttendance['device']['device_code'] !== Constants::DEVICE_APP) {
                $device = Device::find()->where([
                    'device_code' => $employeeAttendance['device']['device_code'], 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
                
                if ($device === null) {
                    $this->addError($attribute.'.device_code', 'Device not found');
                    return;
                }

                $employeeAttendance['device'] = ['id' => $device->id, 'device_code' => $device->device_code, 'device_name' => $device->device_name];
            }

            $employeeAttendance['employee_id'] = intval($employeeAttendance['employee_id']);
            $employee = Employee::find()->where(['id' => $employeeAttendance['employee_id'], 'status' => Constants::STATUS_ACTIVE])->one();
            if ($employee === null) {
                $this->addError($attribute.'['.$key.'].employee_id', 'Employee not found');
                return;
            }

            if ($oldAttendance === null || !isset($oldAttendance[$key])) {
                $division_id = $employee->division_id;
                $division = Division::find()->where([
                    'id' => $division_id, 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
                
                if ($division === null) {
                    $this->addError($attribute.'['.$key.'].employee_id', 'Division not found');
                    return;
                }
                
                $userAttendance = UserAttendance::find()->where([
                    'employee_id' => $employeeAttendance['employee_id'], 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
                
                if ($userAttendance === null) {
                    $this->addError($attribute.'['.$key.'].employee_id', 'Attendance Time not found');
                    return;
                }
                
                $work_time_id = $userAttendance->work_time_id;

                $workTime = WorkTime::find()->where([
                    'id' => $work_time_id, 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();

                if ($workTime === null) {
                    $this->addError($attribute.'['.$key.'].employee_id', 'Work Time not found');
                    return;
                }

                $employeeAttendance['work_time'] = [
                    'id' => $workTime->id, 
                    'entry_time' => $workTime->entry_time, 
                    'home_time' => $workTime->home_time
                ];

                if (!empty($employeeAttendance['start_time']) && strtotime($employeeAttendance['start_time']) === false) {
                    $this->addError($attribute, 'Attendance start time value not valid');
                    return;
                }

                if (!empty($employeeAttendance['end_time']) && strtotime($employeeAttendance['end_time']) === false) {
                    $this->addError($attribute, 'Attendance end time value not valid');
                    return;
                }

                if (!empty($employeeAttendance['start_time']) && !empty($employeeAttendance['work_time']['entry_time'])) {
                    $startTime = strtotime($employeeAttendance['start_time']);
                    $entryTime = strtotime($employeeAttendance['work_time']['entry_time']);
                    $workingDays = $workTime->working_days;
                    $dayNow = date('l', strtotime($this->date));
                    
                    if ($startTime > $entryTime) {
                        if (!in_array("Telat Masuk", $employeeAttendance['info']['detail'])) {
                            $employeeAttendance['info']['detail'][] = "Telat Masuk";
                        }
                    }

                    if (!in_array($dayNow, $workingDays)) {
                        if (!in_array("Lembur", $employeeAttendance['info']['detail'])) {
                            // $employeeAttendance['info']['detail'][] = "Masuk di hari libur";
                            $employeeAttendance['info']['detail'][] = "Lembur";
                        }
                    }
                }
                
                // Blacklist
                if (!empty($latestAttendance) || !empty($this->dateMismatch)) {
                    $blacklist = [
                        'employee_id' => $employeeAttendance['employee_id'],
                        'employee_name' => $employee->name,
                        'date' => $this->date,
                        'start_time' => $employeeAttendance['start_time'],
                        'time' => date('H:i:s')
                    ];

                    if (strtotime($employeeAttendance['start_time']) < strtotime($latestAttendance)) {
                        $blacklist['start_time'] = date('H:i:s');
                    } elseif (!empty($this->dateMismatch)) {
                        $blacklist['date'] = $this->dateMismatch;
                        $this->dateMismatch = null;
                    }
                    
                    $exsistingBlacklist = $this->detail_info['blacklist'] ?? [];
                    
                    if (!empty($exsistingBlacklist)) {
                        array_push($exsistingBlacklist, $blacklist);
                    } else {
                        $exsistingBlacklist[] = $blacklist;
                    }

                    $this->detail_info = array_merge($this->detail_info ?? [], ['blacklist' => $exsistingBlacklist]);
                }

                $employeeAttendance['info']['in'] = true;
                $employeeAttendance['employee_id'] = $employeeAttendance['employee_id'];   
                $employeeAttendance['employee_name'] = $employee->name;
                $employeeAttendance['division'] = ['id' => $division->id, 'name' => $division->name];
                $employeeAttendance['end_time'] = $employeeAttendance['end_time'];
            } else {
                $employeeAttendance = array_merge($employeeAttendance, $oldAttendance[$key]);
                
                if (isset($value['end_time'])){
                    $employeeAttendance['end_time'] = $value['end_time'];
                    $employeeAttendance['info']['out'] = true;
                }

                if (!empty($employeeAttendance['end_time']) && !empty($employeeAttendance['work_time']['home_time'])) {
                    $endTime = strtotime($employeeAttendance['end_time']);
                    $homeTime = strtotime($employeeAttendance['work_time']['home_time']);

                    if ($endTime < $homeTime) {
                        if (!in_array("Pulang Cepat", $employeeAttendance['info']['detail'])) {
                            $employeeAttendance['info']['detail'][] = "Pulang Cepat";
                        }
                    } else {
                        if (($index = array_search("Pulang Cepat", $employeeAttendance['info']['detail'])) !== false) {
                            unset($employeeAttendance['info']['detail'][$index]);
                        }
                    }
                }
                
                if (isset($value['start_time']) && empty($employeeAttendance['start_time'])) {
                    $employeeAttendance['start_time'] = $value['start_time'];
                }
            }
            // var_dump('employeeAttendance - location: ', $employeeAttendance['location']);
            $entryLocation = $employeeAttendance['location']['entry'] ?? null;
            $homeLocation = $employeeAttendance['location']['home'] ?? null;
            // var_dump('homeLocation: ', isset($homeLocation) ? $homeLocation : null);
            $temp_location = [
                'entry' => $entryLocation,
                'home' => $homeLocation,
            ];
            
            if (isset($value['location']) || !empty($value['location'])) {
                $temp_location['entry'] = $entryLocation;
                $temp_location['home'] = $homeLocation;

                if ($entryLocation === null && !empty($value['start_time'])) {
                    $temp_location['entry'] = $value['location'] ?? null;
                }
                
                if ($homeLocation === null && !empty($value['end_time'])) {
                    $temp_location['home'] = $value['location'] ?? null;
                }
                // var_dump('Masuk | temp home: ', $temp_location['home']);
                $employeeAttendance['location'] = $temp_location;
            } else {
                $employeeAttendance['location'] = null;
            }

            $oldAttendance[$key] = $employeeAttendance;
            // var_dump('OldAttendance - location: ', $oldAttendance[$key]['location']);
        }
               
        $this->$attribute = $oldAttendance;
    }

    public function validateAttendanceNotCheckout($attribute, $params)
    {
        $lastAttendance = $this->getOldAttribute($attribute);
        
        foreach ($lastAttendance as $key => $value) {
            if (empty($value['end_time'])) {
                $value['info']['detail'][] = "Tidak Absen Pulang";
                $value['info']['out'] = false;
            }

            $lastAttendance[$key]['info'] = $value['info'];
        }
        
        $this->$attribute = $lastAttendance;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if ($this->date !== $this->system_date) {
                $this->dateMismatch = $this->date;
                $this->date = $this->system_date;
            }

            return true;
        }
        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->change_log = CoreModel::getChangeLog($this, $insert);
            $this->detail_info = array_merge($this->detail_info ?? [], [
                'change_log' => $this->change_log,
                'dayoff' => $this->detail_info['dayoff'] ?? false,
                'blacklist' => $this->detail_info['blacklist'] ?? [],
            ]);
            var_dump('beforeSave');
            return true;
        }
    
        return false;
    }
}