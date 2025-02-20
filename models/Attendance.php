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
use app\exceptions\ErrorMessage;

/**
 * This is the model class for table "absensi".
 *
 * @property int $id
 * @property string $date
 * @property int $period
 * @property bool $dayoff
 * @property int $status
 * @property string $attendance
 * @property string $blacklist
 * @property string $detail_info
 */
class Attendance extends \yii\db\ActiveRecord
{
    public $device;
    public $division;
    public $end_time;
    public $work_time;
    public $start_time;
    public $system_date;
    public $employee_id;
    public $dateMismatch;
    public $employee_name;

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
                [['attendance', 'blacklist', 'detail_info'], 'safe'],
                [['end_time', 'start_time'], 'date', 'format' => 'php:H:i:s'],
                [['date', 'system_date', 'dateMismatch'], 'date', 'format' => 'php:Y-m-d'],
                
                [['date'], 'required'],
                [['date'], 'filter', 'filter' => function($value) {
                    return CoreModel::nullSafe($value);
                }],
                
                [['attendance'], 'validateAttendanceNotCheckout', 'on' => Constants::SCENARIO_UPDATE],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRules($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['date', 'system_date', 'dateMismatch', 'attendance', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['attendance', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'date' => 'Date',
            'period' => 'Period',
            'attendance' => 'Attendance',
            'blacklist' => 'Blacklist',
            'status' => 'Status',
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
            $date = new \DateTime($this->date);
            if ((int) $date->format('d') >= Constants::LAST_DATE) {
                $date->modify('+1 month');
            }
            $this->period = intval($date->format('Ym'));

            if ($this->scenario === Constants::SCENARIO_CREATE) {
                $this->validateAttendance('attendance');
            }

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

    public function validateAttendance($attribute)
    {
        $attendances = $this->$attribute;
        $oldAttendance = $this->getOldAttribute($attribute);
        $defaultAttendance = Yii::$app->params['defaultValue']['attendance'];

        if (!is_array($attendances)) {
            $this->addError($attribute, Yii::t('core', 'array', ['label' => $this->getAttributeLabel($attribute)]));
            throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
        }

        $lastStartTime = $oldAttendance ? array_column($oldAttendance, 'start_time') : [];
        $latestAttendance = !empty($lastStartTime) ? end($lastStartTime) : null;

        foreach ($attendances as $index => $attendance) {
            $allowedFields = Yii::$app->params['allowedFields']['attendance'];
            CoreModel::validateRequiredFields($this, $attribute, $allowedFields, $attendance);

            // Attendance Data Validation
            $attendance['employee_id'] = intval($attendance['employee_id']);
            if (empty($attendance['employee_id']) || !is_numeric($attendance['employee_id'])) {
                $this->addError($attribute, Yii::t('core', 'integer', ['field' => $this->getAttributeLabel('employee_id')]));
                throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
            }
            
            if (empty($attendance['start_time']) && empty($attendance['end_time'])) {
                $this->addError($attribute, Yii::t('app', 'emptyStartTimeEndTime'));
                throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
            }

            if (empty($attendance['device']['code'])) {
                $this->addError($attribute,  Yii::t('core', 'required', ['field' => $this->getAttributeLabel('device')]));
                throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
            }

            if (empty($attendance['location'])) {
                $this->addError($attribute,  Yii::t('core', 'required', ['field' => $this->getAttributeLabel('device')]));
                throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
            }
            // Attendance Data Validation

            // Data Assignment
            if ($oldAttendance === null || !isset($oldAttendance[$index])) {
                $attendanceData = array_merge($defaultAttendance, $attendance);

                $employee = Employee::findOne(['id' => $attendanceData['employee_id'], 'status' => Constants::STATUS_ACTIVE]);
                if ($employee === null) {
                    $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('Employee')]));
                    throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                }

                $division = Division::findOne(['id' => $employee->division_id, 'status' => Constants::STATUS_ACTIVE]);
                if ($division === null) {
                    $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('Division')]));
                    throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                }

                $userAttendance = UserAttendance::findOne([ 'employee_id' => $attendanceData['employee_id'], 'status' => Constants::STATUS_ACTIVE]);
                if ($userAttendance === null) {
                    $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('UserAttendance')]));
                    throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                }

                $workTime = WorkTime::findOne(['id' => $userAttendance->work_time_id, 'status' => Constants::STATUS_ACTIVE]);
                if ($workTime === null) {
                    $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('WorkTime')]));
                    throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                }

                if ($attendanceData['device']['code'] !== Constants::DEVICE_APP) {
                    $device = Device::findOne(['code' => $attendanceData['device']['code'], 'status' => Constants::STATUS_ACTIVE]);
                    if ($device === null) {
                        $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('Device')]));
                        throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                    }
                    $attendanceData['device'] = ['id' => $device->id, 'code' => $device->code, 'name' => $device->name];
                }

                $attendanceData['start_time'] = CoreModel::nullSafe($attendanceData['start_time']);
                if (strtotime($attendanceData['start_time']) === false) {
                    $this->addError($attribute, Yii::t('app', 'startTimeNotValid'));
                    throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                }

                $startTime = strtotime($attendanceData['start_time']);
                $entryTime = strtotime($workTime->entry_time);
                if ($startTime > $entryTime) {
                    $attendanceData['info']['detail'][] = Constants::ATTENDANCE_STATUS_LT;
                }
                
                $workingDays = $workTime->working_days;
                $dayNow = date('l', strtotime($this->date));
                if (!in_array($dayNow, $workingDays)) {
                    $attendanceData['info']['detail'][] = Constants::ATTENDANCE_STATUS_WH;
                }

                // If start_time and end_time is set on one request
                if (!empty($attendance['end_time']) && strtotime($attendance['end_time']) !== false) {
                    $attendanceData['end_time'] = CoreModel::nullSafe($attendanceData['end_time']);
                    $attendanceData['info']['out'] = true;

                    $endTime = strtotime($attendance['end_time']);
                    $homeTime = strtotime($workTime->home_time);
    
                    if ($endTime < $homeTime) {
                        $attendanceData['info']['detail'][] = Constants::ATTENDANCE_STATUS_LE;
                    }
                }
                
                // Blacklist
                if (!empty($latestAttendance) || !empty($this->dateMismatch)) {
                    $blacklist = [];

                    if (strtotime($attendanceData['start_time']) < strtotime($latestAttendance)) {
                        $blacklist = [
                            'employee_id' => $attendanceData['employee_id'],
                            'employee_name' => $employee->name,
                            'date' => $this->date,
                            'start_time' => $attendanceData['start_time'],
                            'time' => date('H:i:s')
                        ];
                        $attendanceData['start_time'] = date('H:i:s');
                    } elseif (!empty($this->dateMismatch)) {
                        $blacklist = [
                            'employee_id' => $attendanceData['employee_id'],
                            'employee_name' => $employee->name,
                            'date' => $this->dateMismatch,
                            'start_time' => $attendanceData['start_time'],
                            'time' => date('H:i:s')
                        ];
                        $attendanceData['start_time'] = date('H:i:s');
                        $this->dateMismatch = null;
                    }
                    
                    $exsistingBlacklist = $this->detail_info['blacklist'] ?? [];
                    
                    if (!empty($blacklist)) {
                        $exsistingBlacklist[] = $blacklist;
                    }

                    $this->detail_info = array_merge($this->detail_info ?? [], ['blacklist' => $exsistingBlacklist]);
                }
                // Blacklist
               
                $attendanceData['info']['in'] = true;
                $attendanceData['employee_name'] = $employee->name;
                $attendanceData['division'] = ['id' => $division->id, 'name' => $division->name];
                $attendanceData['work_time'] = ['id' => $workTime->id, 'entry_time' => $workTime->entry_time, 'home_time' => $workTime->home_time];
            } else {
                $attendanceData = $oldAttendance[$index];

                if (!empty($attendance['start_time']) && empty($attendanceData['start_time'])) {
                    $attendanceData['start_time'] = $attendance['start_time'];
                }
                
                $attendance['end_time'] = CoreModel::nullSafe($attendance['end_time']);
                if (!empty($attendance['end_time'])) {
                    if (strtotime($attendance['end_time']) === false) {
                        $this->addError($attribute, Yii::t('app', 'endTimeNotValid'));
                        throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                    }
    
                    $endTime = strtotime($attendance['end_time']);
                    $homeTime = strtotime($attendanceData['work_time']['home_time']);
    
                    if ($endTime < $homeTime) {
                        $attendanceData['info']['detail'][] = Constants::ATTENDANCE_STATUS_LE;
                    } else {
                        if (($key = array_search(Constants::ATTENDANCE_STATUS_LE, $attendanceData['info']['detail'])) !== false) {
                            unset($attendanceData['info']['detail'][$key]);
                        }
                    }
    
                    $attendanceData['end_time'] = CoreModel::nullSafe($attendance['end_time']);
                    $attendanceData['info']['out'] = true;
                }
            }
            // Data Assignment

            // Validate location
            $entryLocation = $attendanceData['location']['entry'] ?? null;
            $homeLocation = $attendanceData['location']['home'] ?? null;
            $location = ['entry' => $entryLocation, 'home' => $homeLocation];

            if ($attendance['device']['code'] === Constants::DEVICE_APP) {
                $allowedFields = Yii::$app->params['allowedFields']['attendance_location'];

                if (empty($attendance['location'])) {
                    $this->addError($attribute, Yii::t('core', 'required', ['field' => $this->getAttributeLabel($attribute)]));
                    return;
                }

                CoreModel::validateRequiredFields($this, $attribute, $allowedFields, $attendance['location']);
                
                if (!filter_var($attendance['location']['lat'], FILTER_VALIDATE_FLOAT)) {
                    $this->addError($attribute.'[lat]', Yii::t('app', 'invalidLatFormat'));
                    return;
                }
        
                if (!filter_var($attendance['location']['lng'], FILTER_VALIDATE_FLOAT)) {
                    $this->addError($attribute.'[lng]', Yii::t('app', 'invalidLngFormat'));
                    return;
                }

                $latitude = (float) $attendance['location']['lat'];
                $longitude = (float) $attendance['location']['lng'];

                if ($latitude < -90 || $latitude > 90) {
                    $this->addError($attribute, Yii::t('app', 'invalidLat'));
                }
        
                if ($longitude < -180 || $longitude > 180) {
                    $this->addError($attribute, Yii::t('app', 'invalidLng'));
                }

                $image = array_values($attendance['location']['image']);
                $image = array_filter($image, fn($item) => !in_array($item, ['[]', '', null], true));

                if (!empty($attendanceData['start_time']) && $entryLocation === null) {
                    $location['entry'] = [
                        'lat' => $latitude,
                        'lng' => $longitude,
                        'image' => $image
                    ];
                }

                if (!empty($attendanceData['end_time']) && $homeLocation === null) {
                    $location['home'] = [
                        'lat' => $latitude,
                        'lng' => $longitude,
                        'image' => $image
                    ];
                }
            }
            // Validate location

            $attendanceData['location'] = $location;
            $oldAttendance[$index] = $attendanceData;
        }
        
        $this->$attribute = $oldAttendance;
    }

    public function validateAttendanceNotCheckout($attribute)
    {
        $attendance = $this->getOldAttribute($attribute);
        foreach ($attendance as $key => $value) {
            if (empty($value['end_time'])) {
                $value['info']['detail'][] = Constants::ATTENDANCE_STATUS_ICC;
                $value['info']['out'] = false;
            }
            
            $attendance[$key]['info'] = $value['info'];
        }
        
        $this->$attribute = $attendance;
    }
}