<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

/**
 * This is the model class for table "general_affair_location".
 *
 * @property int $id
 * @property string $name
 * @property int $status
 * @property string $detail_info
 */
class GeneralAffairLocation extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'general_affair_location';
    }

    public function rules()
    {
        return [
            [['name'], 'required'],
            [['detail_info'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['status'], 'default', 'value' => 2],
            [['status'], 'integer'],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
            [['status'], 'filter', 'filter' => 'intval']
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'status', 'detail_info'];
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
        return new \app\models\query\GeneralAffairLocationQuery(get_called_class());
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
