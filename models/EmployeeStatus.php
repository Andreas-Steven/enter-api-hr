<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

class EmployeeStatus extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'employee_status';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['status'], 'integer'],
            [['detail_info'], 'safe'],

            [['name'], 'string', 'max' => 255],
            [['name'], 'required', 'on' => Constants::SCENARIO_CREATE],
            
            [['status'], 'default', 'value' => 2],
            [['status'], 'filter', 'filter' => 'intval'],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\EmployeeStatusQuery(get_called_class());
    }

    public function validateStatusChange($attribute, $params, $validator)
    {
        if (!$this->isNewRecord && $this->isAttributeChanged($attribute)) {
            $oldStatus = $this->getOldAttribute($attribute);
            $newStatus = $this->$attribute;

            if ($oldStatus == Constants::STATUS_DELETED && $newStatus != Constants::STATUS_DELETED && !Yii::$app->coreAPI->superAdmin()) {
                $this->addError($attribute, Yii::t('app', 'deletedStatusChange', ['status' => Constants::statusList()[Constants::STATUS_DELETED]]));
            }
        }
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
