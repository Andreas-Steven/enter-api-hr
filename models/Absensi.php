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
            [['blacklist'], 'default', 'value' => null],
            [['detail_info'], 'default', 'value' => null],

            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
            [['status', 'attendance[employee_id]', 'attendance[device][id]', 'attendance[work_time][id]'], 'filter', 'filter' => 'intval'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['date', 'attendance', 'detail_info'];
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

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            if (isset($this->attendance)) {
                $this->attendance = array_merge([
                    'employee_id' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'info' => [
                        'in' => false,
                        'out' => false,
                        'detail' => []
                    ],
                    'device' => [
                        'id'        => null,
                        'name'      => null
                    ],
                    'work_time' => [
                        'id'        => null,
                        'start_time'  => null,
                        'home_time' => null
                    ],
                    'location' => [
                        'lat'       => null,
                        'long'      => null,
                        'image'     => []
                    ]
                ], $this->attendance);
            }

            
            $attendance = $this->attendance;
            $workTimeData = $this->getWorkTimeData($attendance['work_time']['id']);
            $attendance['device']['name'] = $this->getDeviceName($attendance['device']['id']);

            $attendance['employee_id'] = (int) $attendance['employee_id'];
            $attendance['device']['id'] = (int) $attendance['device']['id'];
            $attendance['work_time']['id'] = (int) $attendance['work_time']['id'];
            $attendance['employee_id'] = (int) $attendance['employee_id'];
            $attendance['work_time']['entry_time'] = $workTimeData['entry_time'];
            $attendance['work_time']['home_time'] = $workTimeData['home_time'];
            $this->attendance = $attendance;
            
            $this->date = Yii::$app->coreAPI->htmlPurifier($this->date);
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];

            return true;
        }
        
        return false;
    }

    public function getDeviceName($id)
    {
        $device = Device::findOne($id);
        return $device ? $device->name : null;
    }

    public function getWorkTimeData($id)
    {
        $workTime = WorkTime::findOne($id);

        return $workTime ? [
            'entry_time' => $workTime->entry_time,
            'home_time' => $workTime->home_time,
        ] : null;
    }
}
