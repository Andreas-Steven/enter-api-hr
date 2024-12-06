<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\models\CoreModel;

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
        return array_merge(
            [
                [['detail_info'], 'safe'],
                [['code', 'name'], 'string', 'max' => 255],
                [['code', 'name'], 'required', 'on' => Constants::SCENARIO_CREATE]
            ], 
            CoreModel::getStatusRules($this)
        );
            
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
