<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

/**
 * This is the model class for table "general_affair_product".
 *
 * @property int $id
 * @property string $name
 * @property int $status 0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance
 * @property string $detail_info
 */
class GeneralAffairProduct extends \yii\db\ActiveRecord
{
    public $change_log;

    public static function tableName()
    {
        return 'general_affair_product';
    }

    public function rules()
    {
        return array_merge(
            [
                [['detail_info'], 'safe'],
                [['name'], 'string', 'max' => 255],
                [['name'], 'required', 'on' => Constants::SCENARIO_CREATE],

                [['name', 'status'], function ($attribute, $params, $validator) {
                    CoreModel::validateDependencies($attribute, $params, $validator, $this, [
                        ['className' => 'GeneralAffair', 'field' => ['product_id']],
                    ]);
                }, 'on' => Constants::SCENARIO_UPDATE_LIST],
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
        return new \app\models\query\GeneralAffairProductQuery(get_called_class());
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->name = CoreModel::htmlPurifier($this->name);
            $this->change_log = CoreModel::getChangeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log,
            ];

            return true;
        }

        return false;
    }
}
