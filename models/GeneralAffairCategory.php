<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

/**
 * This is the model class for table "general_affair_category".
 *
 * @property int $id
 * @property string $name
 * @property int $status
 * @property string $detail_info
 */
class GeneralAffairCategory extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'general_affair_category';
    }

    public function rules()
    {
        return array_merge(
            [
                [['name'], 'required'],
                [['detail_info'], 'safe'],
                [['name'], 'string', 'max' => 255],
                [['status'], 'default', 'value' => 2],
            ],
            CoreModel::getStatusRules($this)
        );
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
        return new \app\models\query\GeneralAffairCategoryQuery(get_called_class());
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->change_log = CoreModel::getChangeLog($this, $insert);
            $this->name = CoreModel::htmlPurifier($this->name);

            $this->detail_info = [
                'change_log' => $this->change_log,
            ];
            return true;
        }
        return false;
    }
}
