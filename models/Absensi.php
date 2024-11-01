<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\models\Employee;
use app\models\Department;
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
class Absensi extends \yii\db\ActiveRecord
{
    public $change_log;
    public $employee_id;
    public $start_time;
    public $end_time;
    public $employee_name;
    public $department;
    public $work_time;
    public $device;
    public $device_code;

    public static function tableName()
    {
        return 'absensi';
    }

    public function rules()
    {
        return [
            [['date'], 'required'],
            [['dayoff'], 'boolean'],
            [['status'], 'integer'],
            [['attendance', 'blacklist', 'detail_info'], 'safe', 'skipOnEmpty' => true],
            
            [['status'], 'default', 'value' => 2],
            [['dayoff'], 'default', 'value' => false],

            [['date'], 'date', 'format' => 'php:Y-m-d'],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],

            [['attendance'], 'safe'],
            [['attendance'], 'validateAttendance'],
            [['status', 'employee_id'], 'filter', 'filter' => 'intval'],
            [['status'], 'validateStatusChange', 'on' => Constants::scenarioUpdate()],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['date', 'attendance', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['date', 'attendance', 'detail_info', 'status'];
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

    public function validateStatusChange($attribute, $params, $validator)
    {
        if (!$this->isNewRecord && $this->isAttributeChanged($attribute)) {
            $oldStatus = $this->getOldAttribute($attribute);
            $newStatus = $this->$attribute;

            if ($oldStatus == Constants::STATUS_DELETED && $newStatus != Constants::STATUS_DELETED && !Yii::$app->coreAPI->superAdmin()) {
                $this->addError($attribute, Yii::t('app', 'deletedStatusChange', ['status' => Constants::statusList()[Constants::STATUS_DELETED]]));
            }
        }
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
        $employeeIds = $oldAttendance ? array_column($oldAttendance, 'employee_id') : [];
        $lastStartTime = $oldAttendance ? array_column($oldAttendance, 'start_time') : [];
        $last_start_time = empty($lastStartTime) ? null : end($lastStartTime);
        $defaultAttendance = Yii::$app->params['attendance'];

        foreach ($attendanceData as $key => $value) {
            $employeeAttendance = $defaultAttendance;

            if (isset($value['employee_id'])) {
                $value['employee_id'] = intval($value['employee_id']);
                $employeeIndex = array_search($value['employee_id'], $employeeIds);
                
                if (!is_integer($value['employee_id']) || $value['employee_id'] < 1) {
                    $this->addError($attribute, 'Attendance employee id value must be an integer greater than 0');
                    return;
                }
                
                $employee = Employee::find()->where(['id' => $value['employee_id'], 'status' => Constants::STATUS_ACTIVE])->one();
                if ($employee === null) {
                    $this->addError($attribute.'['.$key.'].employee_id', 'Employee not found');
                    return;
                }
                
                $department_id = $employee->employee_detail['department']['id'];
                $work_time_id = $employee->employee_detail['work_time']['id']; 
                
                $department = Department::find()->where([
                    'id' => $department_id, 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
                
                if ($department === null) {
                    $this->addError($attribute.'['.$key.'].employee_id', 'Department not found');
                    return;
                }
                    
                $workTime = WorkTime::find()->where([
                    'id' => $work_time_id, 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();

                if ($workTime === null) {
                    $this->addError($attribute.'['.$key.'].employee_id', 'Work Time not found');
                    return;
                }
                    
                if (isset($value['device']['device_code'])) {
                    $device = Device::find()->where([
                        'device_code' => $value['device']['device_code'], 
                        'status' => Constants::STATUS_ACTIVE
                    ])->one();
                    
                    if ($device === null) {
                        $this->addError($attribute.'['.$key.'].device_code', 'Device not found');
                        return;
                    }
                }

                $employeeAttendance['employee_id'] = $value['employee_id'] ?? null;   
                $employeeAttendance['employee_name'] = $employee->name;
                $employeeAttendance['department'] = ['id' => $department->id, 'name' => $department->name];
                $employeeAttendance['work_time'] = ['id' => $workTime->id, 'entry_time' => $workTime->entry_time, 'home_time' => $workTime->home_time];
                $employeeAttendance['device'] = ['id' => $device->id, 'device_code' => $device->device_code, 'device_name' => $device->device_name];
            }

            if (!empty($value['start_time']) && strtotime($value['start_time']) === false) {
                $this->addError($attribute, 'Attendance start time value not valid');
                return;
            }
            
            if ($employeeIndex !== false && $value['employee_id'] == $oldAttendance[$employeeIndex + 1]['employee_id']) {
                $value['start_time'] = $oldAttendance[$employeeIndex + 1]['start_time'];
            } elseif (!empty($last_start_time)) {
                if (isset($value['start_time']) && !empty($value['start_time'])) {
                    if (strtotime($value['start_time']) < strtotime($last_start_time)) {
                        $this->addBlacklist($employeeAttendance['employee_id'], $employeeAttendance['start_time'], $this->date);
                        $value['start_time'] = date('H:i:s');
                    }
                } else {
                    $value['start_time'] = null;
                }
            }

            $employeeAttendance['start_time'] = $value['start_time'] ?? null;
           
            if (isset($value['end_time'])) {
                if (!empty($value['end_time']) && strtotime($value['end_time']) === false) {
                    $this->addError($attribute, 'Attendance end time value not valid');
                    return;
                } elseif (empty($value['end_time'])) {
                    $value['end_time'] = null;
                }
            }

            $employeeAttendance['end_time'] = $value['end_time'] ?? null;
            $employeeAttendance = $this->insertInfoField($employeeAttendance);
        }
        
        if (isset($this->id)) {
            if ($employeeIndex !== false) {
                $oldAttendance[$employeeIndex + 1] = $employeeAttendance;
                // var_dump($oldAttendance[$employeeIndex + 1]); exit;
            } else {
                array_push($oldAttendance, $employeeAttendance);
            }
        } else {
            $oldAttendance[1] = $employeeAttendance;
        }

        
        $this->$attribute = $oldAttendance;
    }

    public function insertInfoField($attendance)
	{
        // Set boolean info masuk
        if (!empty($attendance['start_time']) && $attendance['start_time'] != '00:00:00') {
            $attendance['info']['in'] = true;
		} else {
            $attendance['info']['in'] = null;
        }
        
        // Set boolean info pulang
		if (!empty($attendance['end_time']) && $attendance['end_time'] != '00:00:00') {
            $attendance['info']['out'] = true;
		} else {
            $attendance['info']['out'] = null;
        }
        
        // Set detail info telat masuk
		if (!empty($attendance['start_time']) && !empty($attendance['work_time']['entry_time'])) {
            $startTime = strtotime($attendance['start_time']);
			$entryTime = strtotime($attendance['work_time']['entry_time']);
            
			if ($startTime > $entryTime) {
                if (!isset($attendance['info']['detail'])) {
                    $attendance['info']['detail'][] = "Telat Masuk";
				}
			}
		}

        // Detail info pulang cepat
		if (!empty($attendance['end_time']) && !empty($attendance['work_time']['home_time']) && $attendance['end_time'] != '00:00:00') {
			$endTime = strtotime($attendance['end_time']);
			$homeTime = strtotime($attendance['work_time']['home_time']);

			if ($endTime < $homeTime) {
				$attendance['info']['detail'][] = "Pulang Cepat";
			}
		}

        // Set detail info default null
        if(!isset($attendance['info']['detail'])) {
            $attendance['info']['detail'] = null;
        }

        // Tidak absen pulang
		if (($attendance['end_time'] == '00:00:00' || empty($attendance['end_time'])) && !empty($attendance['start_time']) && date('Y-m-d') != date('Y-m-d', strtotime($attendance['start_time']))) {
			if (!in_array("Tidak Absen Pulang", $attendance['info']['detail'])) {
				$attendance['info']['detail'][] = "Tidak Absen Pulang";
			}
		}

		return $attendance;
	}
    
    public function addBlacklist($employee_id, $start_time, $date)
    {
        $blacklist = [
            'employee_id' => $employee_id,
            'start_time' => $start_time,
            'date' => $date
        ];
        $exsistingBlacklist = $this->detail_info['blacklist'];
        
        if (!empty($exsistingBlacklist)) {
            array_push($exsistingBlacklist, $blacklist);
        } else {
            $exsistingBlacklist[] = $blacklist;
        }

        $this->setAttribute('detail_info', array_merge($this->detail_info, ['blacklist' => $exsistingBlacklist]));
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];
            $this->detail_info = Yii::$app->coreAPI->detailInfoAddition($this, $insert);
        } else {
            $detailInfo = $this->detail_info;
            $detailInfo['change_log'] = Yii::$app->coreAPI->changeLog($this, $insert) ?: null;
            $this->detail_info = $detailInfo;
        }

        return parent::beforeSave($insert);
    }
}