<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

/**
 * This is the model class for table "division".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $status
 * @property string $detail_info
 */
class Division extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'division';
    }

    public function rules()
    {
        return [
            [['status'], 'integer'],
            [['detail_info'], 'safe'],
            [['code', 'name'], 'string', 'max' => 255],
            
            [['status'], 'default', 'value' => 2],
            [['code', 'name'], 'required', 'on' => Constants::SCENARIO_CREATE],
            
            [['status'], 'filter', 'filter' => 'intval'],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
            [['status'], 'validateStatusChange', 'on' => Constants::scenarioUpdate()],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'code', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['name', 'code', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'name' => 'Name',
            'status' => 'Status',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\DivisionQuery(get_called_class());
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
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->name = Yii::$app->coreAPI->htmlPurifier($this->name);
            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];

            return true;
        }
        
        return false;
    }
}
