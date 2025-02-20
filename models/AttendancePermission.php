<?php

namespace app\models;

use Yii;
use app\core\CoreModel;
use app\helpers\Constants;
use app\exceptions\ErrorMessage;
use app\models\search\AttendanceSearch;

/**
 * This is the model class for table "attendance_permission".
 *
 * @property int $id
 * @property int $employee_id
 * @property int $permission_type 1: Sick, 2: Sick with receipt, 3: Leave early, 4: Late, 5: Annual leave
 * @property string|null $date
 * @property string|null $note
 * @property int $approved 0: Unapproved, 1: Approved
 * @property string|null $approved_note
 * @property string|null $revision
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 * @property int|null $sync 1: unsync, null: synced
 */
class AttendancePermission extends \yii\db\ActiveRecord
{
    public $employee;
    public $attendance;
    public $approved_at;
    public $approved_by;

    public static function tableName()
    {
        return 'attendance_permission';
    }

    public function optimisticLock() 
    {
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return array_merge(
            [
                [['revision', 'detail_info'], 'safe'],
                [['date'], 'date', 'format' => 'php:Y-m-d'],
                [['note', 'approved_note'], 'string', 'max' => 255],
                [['employee_id', 'permission_type', 'approved', 'status', 'sync'], 'integer'],
                
                [['employee_id', 'permission_type'], 'filter', 'filter' => 'intval'],
                [['employee_id'], 'compare', 'compareValue' => 0, 'operator' => '>', 
                    'message' => Yii::t('core', 'integer', ['field' => $this->getAttributeLabel('employee_id')])
                ],
                [['permission_type'], 'in', 'range' => array_keys(Constants::PERMISSION_LIST)],
                
                [['approved', 'approved_note', 'note', 'status', 'sync'], 'default', 'value' => null],
                [['revision'], 'default', 'value' => Yii::$app->params['defaultValue']['revision'], 'when' => function () {
                    return !in_array($this->permission_type, Constants::REVISION_PERMISSION_LIST);
                }],
                
                [['employee_id', 'permission_type', 'date', 'note'], 'required'],

                [['employee_id'], 'validateEmployee'],
            ], 
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['employee_id', 'permission_type', 'date', 'note', 'revision', 'status', 'detail_info', 'sync'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['employee_id', 'permission_type', 'date', 'note', 'revision', 'status', 'detail_info', 'sync'];
        $scenarios[Constants::SCENARIO_APPROVE] = ['approved', 'approved_note', 'status'];
        $scenarios[Constants::SCENARIO_REJECT] = ['approved', 'status'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status', 'sync'];
        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'employee_id' => 'Employee ID',
            'permission_type' => 'Permission Type',
            'date' => 'Date',
            'note' => 'Note',
            'approved' => 'Approved',
            'approved_note' => 'Approved Note',
            'revision' => 'Revision',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
            'sync' => 'Sync',
        ];
    }

    public static function find()
    {
        return new \app\models\query\AttendancePermissionQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();
        unset($fields['sync']);

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            
            return true;
        }

        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $scenario = $this->scenario;

            switch ($scenario) {
                case Constants::SCENARIO_CREATE:
                case Constants::SCENARIO_UPDATE:
                    $this->validateAttendance('date');
                    $this->validateRevision('revision');
                break;
                case Constants::SCENARIO_APPROVE:
                    $this->approved = Constants::APPROVED_APPROVE;
                    $this->approved_at = Yii::$app->coreAPI->UTCTimestamp();
                    $this->approved_by = Yii::$app->coreAPI->getUsername();
                    $this->approved_note = CoreModel::htmlPurifier(CoreModel::nullSafe($this->approved_note));
                break;
                case Constants::SCENARIO_REJECT:
                    $this->approved = Constants::APPROVED_REJECT;
                break;      
                default:
                break;
            }

            $this->note = CoreModel::htmlPurifier(CoreModel::nullSafe($this->note));
            $this->detail_info = [
                'employee' => $this->employee ?? $this->detail_info['employee'] ?? null,
                'attendance' => $this->attendance ?? $this->detail_info['attendance'] ?? null,
                'change_log' => array_merge(
                    CoreModel::getChangeLog($this, $insert),
                    [
                        'approved_at' => $this->approved_at,
                        'approved_by' => $this->approved_by,
                    ],
                ),
            ];
            
            return true;
        }

        return false;
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert) {

        }
        
        parent::afterSave($insert, $changedAttributes);
    }

    public function validateAttendance($attribute)
    {
        if (in_array($this->permission_type, Constants::REVISION_PERMISSION_LIST)) {
            $date = $this->$attribute ?? $this->getOldAttribute($attribute);
            $employee_id = $this->employee_id;
    
            $params = [
                'date' => $date,
                'employee_id' => $employee_id,
            ];
    
            $searchModel = new AttendanceSearch();
            $dataProvider = $searchModel->search($params);
            $attendanceData = $dataProvider->getModels();
    
            if (empty($attendanceData)) {
                $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('attendance')]));
                throw new ErrorMessage($this, Yii::t('app', 'attendancePermissionFailed'), 422);
            }
    
            foreach ($attendanceData as $attendance) {
                $employeeAttendance = $attendance->attendance[$employee_id];
    
                $this->attendance = [
                    'id' => $attendance->id,
                    'date' => $date,
                    'start_time' => $employeeAttendance['start_time'],
                    'end_time' => $employeeAttendance['end_time'],
                ];
            }
        }
    }

    public function validateRevision($attribute)
    {
        $revision = Yii::$app->params['defaultValue']['revision'];

        if (in_array($this->permission_type, Constants::REVISION_PERMISSION_LIST)) {
            $revision = $this->$attribute ?? $this->getOldAttribute($attribute);
            $allowedFields = Yii::$app->params['allowedFields']['revision'];

            if (!is_array($revision)) {
                $this->addError($attribute, Yii::t('core', 'array', ['label' => $this->getAttributeLabel($attribute)]));
                throw new ErrorMessage($this, Yii::t('app', 'attendancePermissionFailed'), 422);
            }
    
            CoreModel::validateRequiredFields($this, $attribute, $allowedFields, $revision);
            
            $revision['start_time'] = CoreModel::nullSafe($revision['start_time']);
            if (!empty($revision['start_time'])) {
                $start_time = \DateTime::createFromFormat('H:i:s', $revision['start_time']);
                if (empty($start_time) || !$start_time->format('H:i:s')) {
                    $this->addError($attribute, Yii::t('app', 'invalidTimeFormat', ['field' => $this->getAttributeLabel($attribute."[start_time]")]));
                    throw new ErrorMessage($this, Yii::t('app', 'attendancePermissionFailed'), 422);
                }
            }
            
            $revision['end_time'] = CoreModel::nullSafe($revision['end_time']);
            if (!empty($revision['end_time'])) {
                $end_time = \DateTime::createFromFormat('H:i:s', $revision['end_time']);
                if (!$end_time || !$end_time->format('H:i:s')) {
                    $this->addError($attribute, Yii::t('app', 'invalidTimeFormat', ['field' => $this->getAttributeLabel($attribute."[end_time]")]));
                    throw new ErrorMessage($this, Yii::t('app', 'attendancePermissionFailed'), 422);
                }
            }

            if (empty($revision['start_time']) && empty($revision['end_time'])) {
                $this->addError($attribute, Yii::t('app', 'rivisionEmpty'));
                throw new ErrorMessage($this, Yii::t('app', 'attendancePermissionFailed'), 422);
            }
        }

        $this->$attribute = $revision;
    }

    public function validateEmployee($attribute, $params)
    {
        $employee = Employee::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

        if ($employee === null) {
            $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->employee = ['id' => $employee->id, 'name' => $employee->name];
    }
}
