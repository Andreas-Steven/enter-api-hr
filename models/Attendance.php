<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;
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
    public $employee_id;
    public $start_time;
    public $end_time;
    public $employee_name;
    public $division;
    public $work_time;
    public $device;
    public $system_date;
    public $dateMismatch;

    public static function tableName()
    {
        return 'attendance';
    }

    public function optimisticLock() 
    {
        // return Constants::OPTIMISTIC_LOCK;
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
            CoreModel::getStatusRules($this),
            // CoreModel::getLockVersionRules($this),
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

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            if ($this->scenario === Constants::SCENARIO_CREATE) {
                if ($this->date !== $this->system_date) {
                    $this->dateMismatch = $this->date;
                    $this->date = $this->system_date;
                }
            }

            return true;
        }
        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->detail_info = array_merge(
                $this->detail_info ?? [], 
                [
                    'change_log' => CoreModel::getChangeLog($this, $insert),
                    'dayoff' => $this->detail_info['dayoff'] ?? false,
                    'blacklist' => $this->detail_info['blacklist'] ?? [],
                ]
            );

            return true;
        }
    
        return false;
    }

    public function validateAttendance($attribute, $params)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, Yii::t('app', 'mustArray', ['field' => $this->getAttributeLabel('Attendance')]));
            return;
        }

        $employeeAttendance = [];
        $attendanceData = $this->$attribute;
        $oldAttendance = $this->getOldAttribute($attribute);
        $lastStartTime = $oldAttendance ? array_column($oldAttendance, 'start_time') : [];
        $latestAttendance = !empty($lastStartTime) ? end($lastStartTime) : null;

        foreach ($attendanceData as $key => $value) {
            $employeeAttendance = array_merge(Yii::$app->params['defaultValue']['attendance']['attendance_field'], $value);
            if (empty($employeeAttendance['employee_id'])) {
                $this->addError($attribute, Yii::t('core', 'required', ['field' => $this->getAttributeLabel('employee_id')]));
                return;
            }
            
            if (empty($employeeAttendance['start_time']) && empty($employeeAttendance['end_time'])) {
                $this->addError($attribute, Yii::t('app', 'emptyStartTimeEndTime'));
                return;
            }
            
            if (isset($value['end_time'])) {
                if (!empty($value['end_time']) && strtotime($value['end_time']) === false) {
                    $this->addError($attribute,  Yii::t('app', 'endTimeNotValid'));
                    return;
                }
            }
            
            if (!isset($employeeAttendance['device']['code'])) {
                $employeeAttendance['device']['code'] = Constants::DEVICE_APP;
                if (empty($value['location'])) {
                    $this->addError($attribute, Yii::t('app', 'locationRequired'));
                    return;
                }
            }
            
            if ($employeeAttendance['device']['code'] !== Constants::DEVICE_APP) {
                $device = Device::find()->where([
                    'code' => $employeeAttendance['device']['code'], 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
                
                if ($device === null) {
                    $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('Device')]));
                    return;
                }

                $employeeAttendance['device'] = ['id' => $device->id, 'code' => $device->code, 'name' => $device->name];
            }

            $employeeAttendance['employee_id'] = intval($employeeAttendance['employee_id']);
            $employee = Employee::find()->where(['id' => $employeeAttendance['employee_id'], 'status' => Constants::STATUS_ACTIVE])->one();
            if ($employee === null) {
                $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('Employee')]));
                return;
            }

            if ($oldAttendance === null || !isset($oldAttendance[$key])) {
                $division_id = $employee->division_id;
                $division = Division::find()->where([
                    'id' => $division_id, 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
                
                if ($division === null) {
                    $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('Division')]));
                    return;
                }
                
                $userAttendance = UserAttendance::find()->where([
                    'employee_id' => $employeeAttendance['employee_id'], 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
                
                if ($userAttendance === null) {
                    $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('UserAttendance')]));
                    return;
                }
                
                $work_time_id = $userAttendance->work_time_id;
                $workTime = WorkTime::find()->where([
                    'id' => $work_time_id, 
                    'status' => Constants::STATUS_ACTIVE
                ])->one();

                if ($workTime === null) {
                    $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('WorkTime')]));
                    return;
                }

                $employeeAttendance['work_time'] = [
                    'id' => $workTime->id, 
                    'entry_time' => $workTime->entry_time, 
                    'home_time' => $workTime->home_time
                ];

                if (!empty($employeeAttendance['start_time']) && strtotime($employeeAttendance['start_time']) === false) {
                    $this->addError($attribute, Yii::t('app', 'startTimeNotValid'));
                    return;
                }

                if (!empty($employeeAttendance['end_time']) && strtotime($employeeAttendance['end_time']) === false) {
                    $this->addError($attribute, Yii::t('app', 'endTimeNotValid'));
                    return;
                }

                if (!empty($employeeAttendance['start_time']) && !empty($employeeAttendance['work_time']['entry_time'])) {
                    $startTime = strtotime($employeeAttendance['start_time']);
                    $entryTime = strtotime($employeeAttendance['work_time']['entry_time']);
                    $workingDays = $workTime->working_days;
                    $dayNow = date('l', strtotime($this->date));
                  
                    if ($startTime > $entryTime) {
                        if (!in_array(Yii::$app->params['defaultValue']['attendance']['info']['LT'], $employeeAttendance['info']['detail'])) {
                            $employeeAttendance['info']['detail'][] = Yii::$app->params['defaultValue']['attendance']['info']['LT'];
                        }
                    }

                    if (!in_array($dayNow, $workingDays)) {
                        if (!in_array(Yii::$app->params['defaultValue']['attendance']['info']['WH'], $employeeAttendance['info']['detail'])) {
                            $employeeAttendance['info']['detail'][] = Yii::$app->params['defaultValue']['attendance']['info']['WH'];
                        }
                    }
                }
                
                // Blacklist
                if (!empty($latestAttendance) || !empty($this->dateMismatch)) {
                    $blacklist = [];

                    if (strtotime($employeeAttendance['start_time']) < strtotime($latestAttendance)) {
                        $blacklist = [
                            'employee_id' => $employeeAttendance['employee_id'],
                            'employee_name' => $employee->name,
                            'date' => $this->date,
                            'start_time' => $employeeAttendance['start_time'],
                            'time' => date('H:i:s')
                        ];
                        $employeeAttendance['start_time'] = date('H:i:s');
                    } elseif (!empty($this->dateMismatch)) {
                        $blacklist = [
                            'employee_id' => $employeeAttendance['employee_id'],
                            'employee_name' => $employee->name,
                            'date' => $this->dateMismatch,
                            'start_time' => $employeeAttendance['start_time'],
                            'time' => date('H:i:s')
                        ];
                        $employeeAttendance['start_time'] = date('H:i:s');
                        $this->dateMismatch = null;
                    }
                    
                    $exsistingBlacklist = $this->detail_info['blacklist'] ?? [];
                    
                    if (!empty($blacklist)) {
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
                        if (!in_array(Yii::$app->params['defaultValue']['attendance']['info']['LE'], $employeeAttendance['info']['detail'])) {
                            $employeeAttendance['info']['detail'][] = Yii::$app->params['defaultValue']['attendance']['info']['LE'];
                        }
                    } else {
                        if (($index = array_search(Yii::$app->params['defaultValue']['attendance']['info']['LE'], $employeeAttendance['info']['detail'])) !== false) {
                            unset($employeeAttendance['info']['detail'][$index]);
                        }
                    }
                }
                
                if (isset($value['start_time']) && empty($employeeAttendance['start_time'])) {
                    $employeeAttendance['start_time'] = $value['start_time'];
                }
            }

            $entryLocation = $employeeAttendance['location']['entry'] ?? null;
            $homeLocation = $employeeAttendance['location']['home'] ?? null;
            
            if (isset($employeeAttendance['location']['image']) && !empty($employeeAttendance['location']['image'])) {
                $employeeAttendance['location']['image'] = array_values($employeeAttendance['location']['image']);
            }

            $temp_location = [
                'entry' => $entryLocation,
                'home' => $homeLocation
            ];
            
            if (isset($value['location']) || !empty($value['location'])) {
                $temp_location['entry'] = $entryLocation;
                $temp_location['home'] = $homeLocation;

                if ($entryLocation === null && !empty($value['start_time'])) {
                    // $temp_location['entry'] = $value['location'] ?? null;
                    $temp_location['entry']['lat'] = floatval($value['location']['lat']) ?? null;
                    $temp_location['entry']['lng'] = floatval($value['location']['lng']) ?? null;
                    $temp_location['entry']['image'] = $value['location']['image'] ?? null;
                }
                
                if ($homeLocation === null && !empty($value['end_time'])) {
                    // $temp_location['home'] = $value['location'] ?? null;
                    $temp_location['home']['lat'] = floatval($value['location']['lat']) ?? null;
                    $temp_location['home']['lng'] = floatval($value['location']['lng']) ?? null;
                    $temp_location['home']['image'] = $value['location']['image'] ?? null;
                }
        
                $employeeAttendance['location'] = $temp_location;
            } else {
                $employeeAttendance['location'] = null;
            }

            $oldAttendance[$key] = $employeeAttendance;
        }
        
        $this->$attribute = $oldAttendance;
    }

    public function validateAttendanceNotCheckout($attribute)
    {
        $lastAttendance = $this->getOldAttribute($attribute);
        foreach ($lastAttendance as $key => $value) {
            if (empty($value['end_time'])) {
                $value['info']['detail'][] = Yii::$app->params['defaultValue']['attendance']['info']['ICC'];
                $value['info']['out'] = false;
            }
            
            $lastAttendance[$key]['info'] = $value['info'];
        }
        
        $this->$attribute = $lastAttendance;
    }
}