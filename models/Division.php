<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

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
    public static function tableName()
    {
        return 'division';
    }

    public function optimisticLock() 
    {
        // return Constants::OPTIMISTIC_LOCK;
    }

    public function rules()
    {
        return array_merge(
            [
                [['detail_info'], 'safe'],
                [['code'], 'string', 'min' => 2, 'max' => 8],
                [['name'], 'string', 'min' => 3, 'max' => 255],
                
                [['code', 'name'], 'required'],
                [['code'], 'unique', 'on' => [Constants::SCENARIO_CREATE, Constants::SCENARIO_UPDATE], 'when' => function($model) {
                    return $model->isAttributeChanged('code');
                }],

                [['code', 'name', 'status'], function ($attribute, $params, $validator) {
                    CoreModel::validateDependencies($attribute, $params, $validator, $this, [
                        ['className' => 'Employee', 'field' => ['division_id']],
                    ]);
                }, 'on' => Constants::SCENARIO_UPDATE_LIST],
            ], 
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRules($this),
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

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {
            $this->name = CoreModel::htmlPurifier($this->name);

            return true;
        }

        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
            ];

            return true;
        }
        
        return false;
    }
}
