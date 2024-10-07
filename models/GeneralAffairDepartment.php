<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

/**
 * This is the model class for table "general_affair_department".
 *
 * @property int $id
 * @property string $code
 * @property string $name
 * @property int $status
 * @property string $detail_info
 */
class GeneralAffairDepartment extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'general_affair_department';
    }

    public function rules()
    {
        return [
            [['code', 'name'], 'required'],
            [['detail_info'], 'safe'],
            [['status'], 'default', 'value' => 2],
            [['status'], 'integer'],
            [['code', 'name'], 'string', 'max' => 255],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
            [['status'], 'filter', 'filter' => 'intval']
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
        return new \app\models\query\GeneralAffairDepartmentQuery(get_called_class());
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
