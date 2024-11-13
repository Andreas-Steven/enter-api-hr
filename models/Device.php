<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

/**
 * This is the model class for table "device".
 *
 * @property int $id
 * @property string $name
 * @property string $address
 * @property string $location
 * @property string $device_ip
 * @property int $status
 * @property string $detail_info
 */
class Device extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'device';
    }

    public function rules()
    {
        return array_merge(
            [
                [['device_code', 'device_name', 'device_address', 'device_ip'], 'required'],
                [['device_code', 'device_name', 'device_address', 'device_ip'], 'string', 'max' => 255],
                [['detail_info', 'device_location'], 'safe'],

            ],
            Yii::$app->coreAPI->statusRules($this)
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['device_code', 'device_name', 'device_address', 'device_ip', 'device_location', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['device_code', 'device_name', 'device_address', 'device_ip', 'device_location', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'device_code' => 'Device Code',
            'device_name' => 'Device Name',
            'device_address' => 'Device Address',
            'device_ip' => 'Device IP',
            'device_location' => 'Device Location',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\DeviceQuery(get_called_class());
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->device_name = Yii::$app->coreAPI->htmlPurifier($this->device_name);
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];
            
            return true;
        }
        
        return false;
    }
}
