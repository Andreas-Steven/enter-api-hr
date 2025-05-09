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
 * @property int|null $sync_mdb 1: unsync_mdb, null: sync_mdbed
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
                [['note', 'approved_note'], 'string', 'min' => 3, 'max' => 255],
                [['employee_id', 'permission_type', 'approved', 'status', 'sync_mdb'], 'integer'],

                [['employee_id', 'permission_type', 'date', 'note'], 'required'],

                [['employee_id', 'permission_type'], 'filter', 'filter' => 'intval'],
                [['permission_type'], 'in', 'range' => array_keys(Constants::PERMISSION_LIST),],
                [['approved'], 'in', 'range' => array_keys(Constants::PERMISSION_APPROVAL)],

                [['revision'], 'default', 'value' => []],
                [['note', 'approved', 'approved_note', 'status', 'sync_mdb'], 'default', 'value' => null],

                [['employee_id'], 'compare', 'compareValue' => 0, 'operator' => '>', 
                    'message' => Yii::t('app', 'integer', ['label' => $this->getAttributeLabel('employee_id')])
                ],

                [['revision'], function($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, 'Revision');
                }],

                [['employee_id'], 'validateEmployee'],

            ], 
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRulesOnly($this),
        ); 
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['employee_id', 'permission_type', 'date', 'note', 'revision', 'approved', 'approved_note', 'status', 'detail_info', 'sync_mdb'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['employee_id', 'permission_type', 'date', 'note', 'revision', 'status', 'detail_info', 'sync_mdb'];
        $scenarios[Constants::SCENARIO_APPROVE] = ['approved', 'approved_note', 'status'];
        $scenarios[Constants::SCENARIO_REJECT] = ['approved', 'status'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status', 'sync_mdb'];
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
            'sync_mdb' => 'sync_mdb',
        ];
    }

    public static function find()
    {
        return new \app\models\query\AttendancePermissionQuery(get_called_class());
    }

    public function fields()
    {
        $fields = parent::fields();
        unset($fields['sync_mdb']);

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
                    if (in_array($this->permission_type, Constants::REVISION_PERMISSION_LIST)) {
                        $this->validateAttendance('date');
                        $this->validateRevision('revision');
                    } else {
                        $this->revision = Yii::$app->params['defaultValue']['revision'];
                    }
                break;
                case Constants::SCENARIO_APPROVE:
                    $this->approved = intval($this->approved);
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
        $date = $this->$attribute ?? $this->getOldAttribute($attribute);
        $employeeId = $this->employee_id;

        $attendanceModel = Attendance::find()
            ->selectAttendance($employeeId)
            ->where(['date' => $date])
            ->asArray()->one();

        if (empty($attendanceModel)) {
            $this->addError($attribute, Yii::t('app', 'attendanceNotFound', [
                'date' => $date,
            ]));
            throw new ErrorMessage($this, Yii::t('app', 'attendancePermissionFailed'), 422);
        }

        $attendanceModel['detail_attendance'] = json_decode($attendanceModel['detail_attendance'], true);

        $this->attendance = [
            'id' => $attendanceModel['id'],
            'date' => $date,
            'start_time' => $attendanceModel['detail_attendance']['start_time'],
            'end_time' => $attendanceModel['detail_attendance']['end_time'],
        ];
    }

    public function validateRevision($attribute)
    {
        $revision = $this->$attribute ?? $this->getOldAttribute($attribute);
        $allowedFields = Yii::$app->params['allowedFields']['revision'];

        if (empty($revision)) {
            $this->addError($attribute, Yii::t('app', 'required', ['label' => 'Revision']));
            throw new ErrorMessage($this, Yii::t('app', 'attendancePermissionFailed'), 422);
        }

        CoreModel::validateRequiredFields($this, $attribute, $allowedFields, $revision);

        $revision['start_time'] = CoreModel::nullSafe($revision['start_time']);
        $revision['end_time'] = CoreModel::nullSafe($revision['end_time']);
        
        if (empty($revision['start_time']) && empty($revision['end_time'])) {
            $this->addError($attribute, Yii::t('app', 'revisionEmpty', ['value' => 'Start Time revision']));
            throw new ErrorMessage($this, Yii::t('app', 'attendancePermissionFailed'), 422);
        }

        if ($this->permission_type === Constants::PERMISSION_LATE && empty($revision['start_time'])) {
            $this->addError($attribute, Yii::t('app', 'revisionRequired', [
                'label' => 'Start Time revision',
                'value' => $this->getAttributeLabel(Constants::PERMISSION_LIST[$this->permission_type])
            ]));
        }

        if ($this->permission_type === Constants::PERMISSION_LEAVE_EARLY && empty($revision['end_time'])) {
            $this->addError($attribute, Yii::t('app', 'revisionRequired', [
                'label' => 'End Time revision',
                'value' => $this->getAttributeLabel(Constants::PERMISSION_LIST[$this->permission_type])
            ]));
        }
        
        if (!empty($revision['start_time'])) {
            $start_time = \DateTime::createFromFormat('H:i:s', $revision['start_time']);
            if (empty($start_time) || !$start_time->format('H:i:s')) {
                $this->addError($attribute, Yii::t('app', 'invalidTimeFormat', ['label' => 'Start Time revision']));
            }
        }
        
        if (!empty($revision['end_time'])) {
            $end_time = \DateTime::createFromFormat('H:i:s', $revision['end_time']); 
            if (!$end_time || !$end_time->format('H:i:s')) {
                $this->addError($attribute, Yii::t('app', 'invalidTimeFormat', ['label' => 'End Time revision']));
            }
        }

        if ($this->hasErrors()) {
            throw new ErrorMessage($this, Yii::t('app', 'attendancePermissionFailed'), 422);
        }

        $this->$attribute = $revision;
    }

    public function validateEmployee($attribute, $params)
    {
        $employee = Employee::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

        if ($employee === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->employee = ['id' => $employee->id, 'name' => $employee->name];
    }
}
