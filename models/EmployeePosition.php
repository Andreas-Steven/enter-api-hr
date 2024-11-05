<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

/**
 * This is the model class for table "employee_position".
 *
 * @property int $id
 * @property string $name
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 */
class EmployeePosition extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'employee_position';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['name'], 'required', 'on' => Constants::SCENARIO_CREATE],
            [['detail_info'], 'safe'],

            [['status'], 'integer'],
            [['status'], 'default', 'value' => 2],
            [['status'], 'filter', 'filter' => 'intval'],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
            [['status'], 'validateStatusChange', 'on' => Constants::scenarioUpdate()],

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
        return new \app\models\query\EmployeePositionQuery(get_called_class());
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
