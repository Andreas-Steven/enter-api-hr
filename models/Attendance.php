<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\models\Employee;
use app\models\Division;
use app\models\WorkTime;
use app\models\Device;

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

    public static function tableName()
    {
        return 'attendance';
    }

    public function rules()
    {
        return array_merge(
            [
                [['date'], 'required'],
                [['dayoff'], 'boolean'],
                [['attendance', 'blacklist', 'detail_info'], 'safe', 'skipOnEmpty' => true],
                [['dayoff'], 'default', 'value' => false],
                [['date'], 'date', 'format' => 'php:Y-m-d'],
                [['system_date'], 'date', 'format' => 'php:Y-m-d'],
                [['attendance'], 'safe'],
                [['attendance'], 'validateAttendance'],
                [['employee_id'], 'filter', 'filter' => 'intval'],
                [['end_time', 'start_time'], 'date', 'format' => 'php:H:i:s'],
            ],
            Yii::$app->coreAPI->statusRules($this)
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['date', 'attendance', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['date', 'attendance', 'status', 'detail_info'];
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
        
        foreach ($attendanceData as $key => $value) {
            $employeeAttendance = Yii::$app->params['attendance'];
            $employeeAttendance = array_merge($employeeAttendance, $value);
            
            // $nonLocationData = array_diff_key($value, ['location' => 1, 'lat' => 1, 'lng' => 1]);
            // $employeeAttendance = array_merge($employeeAttendance, $nonLocationData);
            // var_dump($nonLocationData, $employeeAttendance); exit;

            if (isset($value['location']) && !empty($value['start_time'])) {
                $employeeAttendance['location']['entry'] = ['lat' => $value['location']['lat'], 'lng' => $value['location']['lng']];
            }

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
                if (!isset($employeeAttendance['location'])) {
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

            if ($oldAttendance === null || !isset($oldAttendance[$key])) {
                $employeeAttendance['employee_id'] = intval($employeeAttendance['employee_id']);

                $employee = Employee::find()->where(['id' => $employeeAttendance['employee_id'], 'status' => Constants::STATUS_ACTIVE])->one();
                if ($employee === null) {
                    $this->addError($attribute.'['.$key.'].employee_id', 'Employee not found');
                    return;
                }

                $division_id = $employee->division_id;
                $division = Division::find()->where([
                    'id' => $division_id, 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
                
                if ($division === null) {
                    $this->addError($attribute.'['.$key.'].employee_id', 'Division not found');
                    return;
                }
                
                // $work_time_id = $employee->employee_detail['work_time']['id']; 
                $work_time_id = 1;
                $workTime = WorkTime::find()->where([
                    'id' => $work_time_id, 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();

                if ($workTime === null) {
                    $this->addError($attribute.'['.$key.'].employee_id', 'Work Time not found');
                    return;
                }

                $employeeAttendance['work_time'] = ['id' => $workTime->id, 'entry_time' => $workTime->entry_time, 'home_time' => $workTime->home_time];

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
                    
                    if ($startTime > $entryTime) {
                        if (!in_array("Telat Masuk", $employeeAttendance['info']['detail'])) {
                            $employeeAttendance['info']['detail'][] = "Telat Masuk";
                        }
                    }
                }

                $employeeAttendance['info']['in'] = true;
                $employeeAttendance['employee_id'] = $employeeAttendance['employee_id'];   
                $employeeAttendance['employee_name'] = $employee->name;
                $employeeAttendance['division'] = ['id' => $division->id, 'name' => $division->name];
                $employeeAttendance['end_time'] = $employeeAttendance['end_time'];
            } else {
                $employeeAttendance = array_merge($employeeAttendance, $oldAttendance[$key]);

                if (isset($value['location']) && !empty($value['start_time'])) {
                    $employeeAttendance['location']['home'] = ['lat' => $value['location']['lat'], 'lng' => $value['location']['lng']];
                }
                
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
                    }
                }
                
                if (isset($value['start_time']) && empty($employeeAttendance['start_time'])) {
                    $employeeAttendance['start_time'] = $value['start_time'];
                }
            }

            $oldAttendance[$key] = $employeeAttendance;
        }
        
        $this->$attribute = $oldAttendance;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);

            $this->detail_info = [
                'change_log' => $this->change_log,
                'dayoff' => false,
                'blacklist' => [],
            ];
            
            return true;
        }
        
        return false;
    }

    public function addBlacklist($employee_id, $start_time, $name, $date)
    {
        $blacklist = [
            'employee_id' => $employee_id,
            'employee_name' => $name,
            'start_time' => $start_time,
            'date' => $date,
            'time' => date('H:i:s')
        ];
        $exsistingBlacklist = $this->detail_info['blacklist'];
        
        if (!empty($exsistingBlacklist)) {
            array_push($exsistingBlacklist, $blacklist);
        } else {
            $exsistingBlacklist[] = $blacklist;
        }

        $this->setAttribute('detail_info', array_merge($this->detail_info, ['blacklist' => $exsistingBlacklist]));
    }

    // Tidak absen pulang
	// 	if (!empty($attendance['end_time']) && !empty($attendance['start_time']) && date('Y-m-d') != date('Y-m-d', strtotime($attendance['start_time'])) {
	// 		if (!in_array("Tidak Absen Pulang", $attendance['info']['detail'])) {
	// 			$attendance['info']['detail'][] = "Tidak Absen Pulang";
	// 		}
	// 	}
}