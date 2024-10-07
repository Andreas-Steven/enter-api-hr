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
        return [
            [['name', 'address', 'location', 'device_ip'], 'required'],
            [['name', 'address', 'device_ip'], 'string', 'max' => 255],
            [['detail_info', 'location'], 'safe'],
            [['status'], 'integer'],

            [['status'], 'default', 'value' => 2],
            [['status'], 'filter', 'filter' => 'intval'],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'address', 'location', 'device_ip'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'address', 'location', 'device_ip', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'address' => 'Address',
            'location' => 'Location',
            'device_ip' => 'Device Ip',
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
            $this->name = Yii::$app->coreAPI->htmlPurifier($this->name);
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);

            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];
            
            return true;
        }
        
        return false;
    }
}
