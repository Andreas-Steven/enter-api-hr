<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

/**
 * This is the model class for table "device".
 *
 * @property int $id
 * @property string $name
 * @property string $address
 * @property string $location
 * @property string $ip_address
 * @property int $status
 * @property string $detail_info
 */
class Device extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'device';
    }

    /**
     * {@inheritdoc}
     * @return string the column name for optimistic locking of $lockVersion.
     */
    public function optimisticLock() {
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return array_merge(
            [
                [['detail_info', 'location'], 'safe'],
                [['code', 'name', 'address', 'ip_address'], 'required'],
                [['code', 'name', 'address', 'ip_address'], 'string', 'max' => 255],

                [['code'], 'unique', 'on' => Constants::SCENARIO_CREATE],
                [['location'], 'validateLocation'],
            ],
            CoreModel::getStatusRules($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['code', 'name', 'address', 'ip_address', 'location', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['code', 'name', 'address', 'ip_address', 'location', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'name' => 'Name',
            'address' => 'Address',
            'ip_address' => 'IP Address',
            'location' => 'Location',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\DeviceQuery(get_called_class());
    }

    /**
     * Returns an array of fields that should be returned by default by `toArray()` when no specific fields are specified.
     * @return array the list of fields that can be exported. Defaults to all attributes of the model.
     */
    public function fields()
    {
        $fields = parent::fields();

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
            $this->name = CoreModel::htmlPurifier($this->name);
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];
            
            return true;
        }
        
        return false;
    }

    public function validateLocation($attribute, $params)
    {
        if (!empty($this->location)) {
            $location = $this->location;
            $location['lat'] = floatval($location['lat']);
            $location['lng'] = floatval($location['lng']);
            $this->location = $location;
        }
    }
}
