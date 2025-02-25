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

    public function optimisticLock() 
    {
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return array_merge(
            [
                [['ip_address'], 'ip'],
                [['code'], 'string', 'max' => 5],
                [['name', 'address'], 'string', 'max' => 255],
                [['location', 'detail_info'], 'safe'],

                [['code', 'ip_address'], 'unique', 'on' => Constants::SCENARIO_CREATE],
                [['code', 'name', 'address', 'ip_address'], 'required'],
                
                [['location'], 'validateLocation'],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRules($this),
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
        $location = $this->$attribute;
        $allowedFields = Yii::$app->params['allowedFields']['location'];

        if (empty($location)) {
            $this->addError($attribute, Yii::t('core', 'required', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        CoreModel::validateRequiredFields($this, $attribute, $allowedFields, $location);
        
        if (!filter_var($location['lat'], FILTER_VALIDATE_FLOAT)) {
            $this->addError($attribute.'[lat]', Yii::t('app', 'invalidLatFormat'));
            return;
        }

        if (!filter_var($location['lng'], FILTER_VALIDATE_FLOAT)) {
            $this->addError($attribute.'[lng]', Yii::t('app', 'invalidLngFormat'));
            return;
        }

        $latitude = (float) $location['lat'];
        $longitude = (float) $location['lng'];

        if ($latitude < -90 || $latitude > 90) {
            $this->addError($attribute, Yii::t('app', 'invalidLat'));
        }

        if ($longitude < -180 || $longitude > 180) {
            $this->addError($attribute, Yii::t('app', 'invalidLng'));
        }

        $this->$attribute = [
            'lat' => $latitude,
            'lng' => $longitude,
        ];
    }
}
