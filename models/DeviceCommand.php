<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

class DeviceCommand extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'device_command';
    }

    public function rules()
    {
        return [
            [['status'], 'integer'],
            [['sn', 'command', 'response'], 'string'],
            
            [['sn', 'command', 'status'], 'required'],
        ];
    }
}