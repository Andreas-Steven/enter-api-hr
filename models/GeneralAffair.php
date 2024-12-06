<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\models\CoreModel;

/**
 * This is the model class for table "general_affair".
 *
 * @property int $id
 * @property string $name
 * @property int $category_id
 * @property int $location_id
 * @property int $status
 * @property string $detail_info
 * @property string $asset
 *
 * @property GeneralAffairCategory $category
 * @property GeneralAffairLocation $location
 * @property Division $divison
 */
class GeneralAffair extends \yii\db\ActiveRecord
{
    public $change_log;
    public $code;
    public $location;

    public static function tableName()
    {
        return 'general_affair';
    }
    

    public function rules()
    {
        return array_merge(
            [
                [['asset', 'code'], 'safe'],
                [['name'], 'string', 'max' => 255],
                [['category_id', 'location_id'], 'integer'],
                [['name', 'category_id', 'location_id'], 'required'],
                [['detail_info', 'asset'], 'safe', 'skipOnEmpty' => true],
                [['category_id', 'location_id'], 'filter', 'filter' => 'intval'],
                
                [['asset'], 'validateItem', 'on' => Constants::SCENARIO_CREATE],
                [['code', 'status'], 'validateDeleted', 'on' => Constants::SCENARIO_DELETE],
                [['location_id'], 'validateLocation'],
            ],
            CoreModel::getStatusRules($this)
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'category_id', 'location_id', 'asset', 'status',  'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['id', 'name', 'category_id', 'location_id', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['code', 'status'];
        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'category_id' => 'Category ID',
            'location_id' => 'Location ID',
            'detail_info' => 'Detail Info',
            'asset' => 'Asset',
        ];
    }

    public static function find()
    {
        return new \app\models\query\GeneralAffairQuery(get_called_class());
    }

    
    public function validateLocation($attribute, $params, $validator)
    {
        $location = isset($this->$attribute) ? $this->detail_info['location'] : null;

        if ($this->isAttributeChanged($attribute)) {
            $location = GeneralAffairLocation::find()->where([
                'id' => $this->$attribute,
                'status' => Constants::STATUS_ACTIVE
            ])->one();
        }

        if ($location === null) {
            $this->addError($attribute, 'Location not found');
            return;
        }

        $this->location = ['id' => $location->id, 'name' => $location->name];
    }

    public function validateItem($attribute, $params, $validator)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, 'Asset must be an array');
            return;
        }

        $generalAffairItems = [];
        $itemData = $this->$attribute;
        $oldItem = $this->getOldAttribute($attribute);
    
        foreach ($itemData['items'] as $key => $value) {
            $timestamp = Yii::$app->coreAPI->getTimestamp();
            $username = Yii::$app->session->get('username') ?? 'Admin';

            if (empty($value['code'])) {
                $this->addError($attribute.'['.$key.'].code', 'Asset code cannot be empty');
                return;
            }
            
            if (isset($oldItem['items'][$value['code']])) {
                $generalAffairItems = array_merge($oldItem['items'][$value['code']], $value);
                $generalAffairItems['items_change_log']['updated_at'] = $timestamp;
                $generalAffairItems['items_change_log']['updated_by'] = $username;
            } else {
                $generalAffairItems = array_merge(Yii::$app->params['items'], $value);
                $generalAffairItems['items_change_log']['created_at'] = $timestamp;
                $generalAffairItems['items_change_log']['created_by'] = $username;
            }

            $generalAffairItems['price'] = floatval($generalAffairItems['price']);
            if (empty($generalAffairItems['price']) || $generalAffairItems['price'] < 0) {
                $this->addError($attribute.'['.$key.'].price', 'Price value must be a float and greater than or equal to 0');
                return;
            } 
    
            $generalAffairItems['condition'] = intval($generalAffairItems['condition']);
            if (empty($generalAffairItems['condition']) || $generalAffairItems['condition'] < 1) {
                $this->addError($attribute.'['.$key.'].condition', 'Condition value must be an integer greater than 0');
                return;
            } 
    
            if (empty($generalAffairItems['division']['id'])) {
                $this->addError($attribute.'['.$key.'].division.id', 'Items division cannot be empty');
                return;
            }
    
            $division = Division::find()->where([
                'id' => $generalAffairItems['division']['id'], 
                'status' => Constants::STATUS_ACTIVE
            ])->one();
            
            if (!$division) {
                $this->addError($attribute.'['.$key.'].division.id', 'Division not found or inactive');
                return;
            }
            $generalAffairItems['division'] = ['id' => $division->id, 'name' => $division->name];
    
            $dateValidator = new \yii\validators\DateValidator(['format' => 'php:Y-m-d']);
            if (isset($generalAffairItems['buying_date']) && !$dateValidator->validate($generalAffairItems['buying_date'], $error)) {
                $this->addError($attribute.'['.$key.'].items.buying_date', $error);
                return;
            }
    
            if (isset($generalAffairItems['tax_date']) && !$dateValidator->validate($generalAffairItems['tax_date'], $error)) {
                $this->addError($attribute.'['.$key.'].items.tax_date', $error);
                return;
            }
    
            if (isset($generalAffairItems['inspection_date']) && !$dateValidator->validate($generalAffairItems['inspection_date'], $error)) {
                $this->addError($attribute.'['.$key.'].items.inspection_date', $error);
                return;
            }
            
            $oldItem['items'][$generalAffairItems['code']] = $generalAffairItems;
        }

        $this->$attribute = ['items' => $oldItem['items']];
    }
    
    public function validateDeleted($attribute, $params, $validator)
    {
        $oldData = $this->getAttributes();
        $itemCodes = array_column($oldData['asset']['items'], 'code') ?? [];
        $timestamp = Yii::$app->coreAPI->getTimestamp();

        if (!empty($this->code) && is_array($oldData['asset']) && array_key_exists('items', $oldData['asset'])) {
            if (in_array($this->code, $itemCodes, true)) {
                $oldData['asset']['items'][$this->code]['items_change_log']['deleted_at'] = $timestamp;
                $oldData['asset']['items'][$this->code]['items_change_log']['deleted_by'] = $username ?? 'Admin';

                $this->setAttribute('asset', $oldData['asset']);
                return true;
            }
        } elseif (isset($this->status) && $this->status === Constants::STATUS_DELETED) {
            if (!$this->isNewRecord && $this->isAttributeChanged($attribute)) {
                $oldStatus = $this->getOldAttribute($attribute);
                $newStatus = $this->$attribute;

                if ($oldStatus == Constants::STATUS_DELETED && $newStatus != Constants::STATUS_DELETED && !Yii::$app->coreAPI->superAdmin()) {
                    $this->addError($attribute, Yii::t('app', 'deletedStatusChange', ['status' => Constants::statusList()[Constants::STATUS_DELETED]]));
                }
            }

            return true;
        }

        $this->addError($attribute, Yii::t('app', 'deleteRecordFailed'));
        return false;
    }

    public function calculateItems($asset)
    {
        $totalPrice = 0;
        $quantity = 0;

        foreach ($asset['items'] as $key => $value) {
            if (is_array($value) && array_key_exists('price', $value) && $value['items_change_log']['deleted_at'] === null) {
                $totalPrice += $value['price'];
                $quantity++;
            }
        }

        $asset['quantity'] = $quantity;
        $asset['total_price'] = $totalPrice;

        return $asset;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->name = CoreModel::htmlPurifier($this->name);
            $this->asset = $this->calculateItems($this->asset);
            $this->change_log = CoreModel::getChangeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log,
                'location' => $this->location,
            ];

            var_dump($this->location);exit;

            return true;
        }

        return false;
    }
}