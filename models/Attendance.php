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
    public $dayoff;
    public $end_time;
    public $division;
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
                [['dayoff'], 'boolean'],
                [['attendance', 'blacklist', 'detail_info'], 'safe'],
                [['end_time', 'start_time'], 'date', 'format' => 'php:H:i:s'],
                [['date', 'system_date', 'dateMismatch'], 'date', 'format' => 'php:Y-m-d'],
                
                [['date'], 'required'],
                [['date'], 'filter', 'filter' => function($value) {
                    return CoreModel::nullSafe($value);
                }],
                
                [['attendance'], function($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, 'Attendance');
                }],
                
                [['attendance'], 'validateAttendanceNotCheckout', 'on' => Constants::SCENARIO_UPDATE],

            ],
            CoreModel::getStatusRules($this),
            // CoreModel::getLockVersionRules($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['date', 'system_date', 'dateMismatch', 'attendance', 'dayoff', 'status', 'detail_info'];
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

                if (empty($this->getDirtyAttributes())) {
                    $this->addError('attendance', Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel('attendance')]));
                    throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
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
                $date->setDate($date->format('Y'), $date->format('m'), 1);
                $date->modify('+1 month');
            }
            $this->period = intval($date->format('Ym'));

            if ($this->scenario === Constants::SCENARIO_CREATE && $this->isAttributeChanged('attendance')) {
                $this->validateAttendance('attendance');
            }

            $this->detail_info = array_merge(
                $this->detail_info ?? [], 
                [
                    'change_log' => CoreModel::getChangeLog($this, $insert),
                    'dayoff' => $this->detail_info['dayoff'] ?? (bool) $this->dayoff,
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

        $lastStartTime = $oldAttendance ? array_column($oldAttendance, 'start_time') : [];
        $latestAttendance = !empty($lastStartTime) ? end($lastStartTime) : null;
        $allowedFields = Yii::$app->params['allowedFields']['attendance'];

        foreach ($attendances as $index => $attendance) {
            CoreModel::validateRequiredFields($this, $attribute, $allowedFields, $attendance);

            // Attendance Data Validation
            $attendance['employee_id'] = intval($attendance['employee_id']);
            if (empty($attendance['employee_id']) || !is_numeric($attendance['employee_id'])) {
                $this->addError($attribute, Yii::t('app', 'integer', ['label' => $this->getAttributeLabel('employee_id')]));
                throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
            }
            
            if (empty($attendance['start_time']) && empty($attendance['end_time'])) {
                $this->addError($attribute, Yii::t('app', 'emptyStartTimeEndTime'));
                throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
            }

            if (empty($attendance['device']['code'])) {
                $this->addError($attribute,  Yii::t('app', 'required', ['label' => $this->getAttributeLabel('device')]));
                throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
            }

            if (empty($attendance['location'])) {
                $this->addError($attribute,  Yii::t('app', 'required', ['label' => $this->getAttributeLabel('device')]));
                throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
            }
            // Attendance Data Validation

            // Data Assignment
            if ($oldAttendance === null || !isset($oldAttendance[$index])) {
                $attendanceData = array_merge($defaultAttendance, $attendance);

                $employeeModel = Employee::findOne([
                    'id' => $attendanceData['employee_id'], 
                ]);
                
                if ($employeeModel === null) {
                    $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel('UserAttendance')]));
                    throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                }

                $employee = [
                    'id' => $employeeModel->id,
                    'name' => $employeeModel->name,
                ];
                
                $division = $employeeModel->detail_info['division'];
                if ($division === null) {
                    $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel('Division')]));
                    throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                }
                
                $workTime = $employeeModel->detail_info['work_time'];
                if ($workTime === null) {
                    $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel('WorkTime')]));
                    throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                }

                if ($attendanceData['device']['code'] !== Constants::DEVICE_APP) {
                    $device = Device::findOne(['code' => $attendanceData['device']['code'], 'status' => Constants::STATUS_ACTIVE]);
                    if ($device === null) {
                        $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel('Device')]));
                        throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                    }
                    $attendanceData['device'] = ['id' => $device->id, 'code' => $device->code, 'name' => $device->name];
                }

                $attendanceData['start_time'] = CoreModel::nullSafe($attendanceData['start_time']);
                if (strtotime($attendanceData['start_time']) === false) {
                    $this->addError($attribute, Yii::t('app', 'startTimeNotValid'));
                    throw new ErrorMessage($this, Yii::t('app', 'attendanceFailed'), 422);
                }

                $dayNow = date('N', strtotime($this->date));
                $dayoff = $this->dayoff ?? $this->detail_info['dayoff'];

                if (isset($workTime['working_days'][$dayNow])) {
                    $entryTime = $workTime['working_days'][$dayNow]['entry_time'];
                    $homeTime = $workTime['working_days'][$dayNow]['home_time'];

                    if ($entryTime !== null && !$dayoff) {
                        $startTime = strtotime($attendanceData['start_time']);
                        $entryTime = strtotime($entryTime);

                        if ($startTime > $entryTime) {
                            $attendanceData['info']['detail'][] = Constants::ATTENDANCE_LATE;
                        }
                    }
            
                    $attendanceData['end_time'] = CoreModel::nullSafe($attendance['end_time']);
                    if (!empty($attendance['end_time']) && strtotime($attendance['end_time']) !== false) {
                        $attendanceData['info']['out'] = true;
                        
                        if ($homeTime !== null && !$dayoff) {
                            $endTime = strtotime($attendance['end_time']);
                            $homeTime = strtotime($homeTime);

                            if ($endTime < $homeTime) {
                                $attendanceData['info']['detail'][] = Constants::ATTENDANCE_LEAVE_EARLY;
                            }
                        }
                    }

                    if ($entryTime === null && $homeTime === null || $dayoff) {
                        if (!empty($attendanceData['start_time'])) {
                            $attendanceData['info']['detail'][] = Constants::ATTENDANCE_WORK_HOLIDAY;
                        }
                    }
                }
                
                // Blacklist
                if (!empty($latestAttendance) || !empty($this->dateMismatch)) {
                    $blacklist = [];

                    if (strtotime($attendanceData['start_time']) < strtotime($latestAttendance)) {
                        $blacklist = [
                            'employee_id' => $attendanceData['employee_id'],
                            'employee_name' => $employee['name'],
                            'date' => $this->date,
                            'start_time' => $attendanceData['start_time'],
                            'time' => date('H:i:s')
                        ];

                        $attendanceData['start_time'] = date('H:i:s');
                    } elseif (!empty($this->dateMismatch)) {
                        $blacklist = [
                            'employee_id' => $attendanceData['employee_id'],
                            'employee_name' => $employee['name'],
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
                $attendanceData['employee_name'] = $employee['name'];
                $attendanceData['division'] = ['id' => $division['id'], 'name' => $division['name']];
                $attendanceData['work_time'] = ['id' => $workTime['id'], 'working_days' => [$dayNow => $workTime['working_days'][$dayNow]]];
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

                    $dayNow = date('N', strtotime($this->date));
                    $homeTime = $attendanceData['work_time']['working_days'][$dayNow]['home_time'];
                    $attendanceData['end_time'] = CoreModel::nullSafe($attendance['end_time']);
                    $endTime = strtotime($attendance['end_time']);
                    
                    if ($endTime !== null) {
                        $homeTime = strtotime($homeTime);

                        if ($endTime < $homeTime) {
                            $attendanceData['info']['detail'][] = Constants::ATTENDANCE_LEAVE_EARLY;
                        } else {
                            if (($key = array_search(Constants::ATTENDANCE_LEAVE_EARLY, $attendanceData['info']['detail'])) !== false) {
                                unset($attendanceData['info']['detail'][$key]);
                                $attendanceData['info']['detail'] = array_values($attendanceData['info']['detail']);
                            }
                        }
                    }

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
                    $this->addError($attribute, Yii::t('app', 'required', ['label' => $this->getAttributeLabel($attribute)]));
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

                $attendance['location']['image'] = array_filter($attendance['location']['image'], function($item) {
                    if (in_array($item, ['[]', '', 'null', null], true)) {
                        $this->addError('location', Yii::t('app', 'required', ['label' => 'Image']));
                        return false;
                    }

                    if (!filter_var($item, FILTER_VALIDATE_URL)) {
                        $this->addError('location', Yii::t('app', 'invalidImage', ['label' => 'Image']));
                        return false;
                    }

                    return true;
                });

                if (!empty($attendanceData['start_time']) && $entryLocation === null) {
                    $location['entry'] = [
                        'lat' => $latitude,
                        'lng' => $longitude,
                        'image' => array_values($attendance['location']['image'])
                    ];
                }

                if (!empty($attendanceData['end_time']) && $homeLocation === null) {
                    $location['home'] = [
                        'lat' => $latitude,
                        'lng' => $longitude,
                        'image' => array_values($attendance['location']['image'])
                    ];
                }
            }
            // Validate location

            $attendanceData['location'] = $location;
            $oldAttendance[$index] = $attendanceData;

            if (empty($latestAttendance)) {
                $latestAttendance = $attendanceData['start_time'];
            }
        }
        
        $this->$attribute = $oldAttendance;
    }

    public function validateAttendanceNotCheckout($attribute)
    {
        $attendance = $this->getOldAttribute($attribute);
        foreach ($attendance as $key => $value) {
            if (empty($value['end_time'])) {
                $value['info']['detail'][] = Constants::ATTENDANCE_MISSED_CHECKOUT;
                $value['info']['out'] = false;
            }
            
            $attendance[$key]['info'] = $value['info'];
        }
        
        $this->$attribute = $attendance;
    }
}