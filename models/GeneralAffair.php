<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;

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
    /**
     * TODO:
     * [x] Add allowed filed validation for General Affair Items
     */

    public $code;
    public $product;
    public $category;
    public $location;

    public static function tableName()
    {
        return 'general_affair';
    }

    public function optimisticLock() 
    {
        // return Constants::OPTIMISTIC_LOCK;
    }
    
    public function rules()
    {
        return array_merge(
            [
                [['name'], 'string', 'max' => 255],
                [['asset', 'code', 'detail_info'], 'safe'],
                [['product_id', 'category_id', 'location_id'], 'integer'],
                
                [['product_id', 'category_id', 'location_id'], 'filter', 'filter' => 'intval'],
                [['product_id', 'category_id', 'location_id'], 'required'],
                
                [['product_id'], 'validateProduct'],
                [['category_id'], 'validateCategory'],
                [['location_id'], 'validateLocation'],
                [['asset'], 'validateAsset', 'on' => Constants::SCENARIO_CREATE],
                [['code', 'status'], 'validateAssetDeleted', 'on' => Constants::SCENARIO_DELETE],
            ],
            CoreModel::getStatusRules($this),
            CoreModel::getLockVersionRules($this),
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['product_id', 'name', 'category_id', 'location_id', 'asset', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['product_id', 'name', 'category_id', 'location_id', 'status', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['code', 'status'];
        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
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

    public function fields()
    {
        $fields = parent::fields();

        return $fields;
    }

    public function beforeValidate()
    {
        if (parent::beforeValidate()) {

            return true;
        }

        return false;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->name = CoreModel::htmlPurifier($this->name);
            $this->asset = $this->calculateAsset($this->asset);
            $this->detail_info = [
                'change_log' => CoreModel::getChangeLog($this, $insert),
                'product' => $this->detail_info['product'] ?? $this->product,
                'category' => $this->detail_info['category'] ?? $this->category,
                'location' => $this->detail_info['location'] ?? $this->location,
            ];

            return true;
        }

        return false;
    }

    public function validateAsset($attribute, $params)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, Yii::t('app', 'mustArray', ['field' => $this->getAttributeLabel('Asset')]));
            return;
        }

        $generalAffairItems = [];
        $itemData = $this->$attribute;
        $oldItem = $this->getOldAttribute($attribute);

        foreach ($itemData['items'] as $key => $value) {
            $timestamp = Yii::$app->coreAPI->UTCTimestamp();
            $username = Yii::$app->coreAPI->getUsername();

            $allowedFields = array_keys(Yii::$app->params['defaultValue']['items']);
            $fields = array_keys($value);
            
            foreach ($fields as $fieldsValue) {
                if (!in_array($fieldsValue, $allowedFields, true)) {
                    $this->addError($attribute, Yii::t('core', 'allowedField', [
                        'field' => $this->getAttributeLabel($attribute), 
                        'value' => implode(', ', $allowedFields)
                    ]));
                    return;
                }
            }

            if (empty($value['code'])) {
                $this->addError($attribute, Yii::t('core', 'nullField', [
                    'label' => $this->getAttributeLabel($attribute.' Code'),
                    'fields' => $this->getAttributeLabel($attribute.' Code'),
                ]));
                return;
            }
            
            if (isset($oldItem['items'][$value['code']])) {
                $generalAffairItems = array_merge($oldItem['items'][$value['code']], $value);
                $generalAffairItems['items_change_log']['updated_at'] = $timestamp;
                $generalAffairItems['items_change_log']['updated_by'] = $username;
            } else {
                $generalAffairItems = array_merge(Yii::$app->params['defaultValue']['items'], $value);
                $generalAffairItems['items_change_log']['created_at'] = $timestamp;
                $generalAffairItems['items_change_log']['created_by'] = $username;
            }

            $generalAffairItems['price'] = floatval($generalAffairItems['price']);  
            if (empty($generalAffairItems['price']) || $generalAffairItems['price'] < 0) {
                $this->addError($attribute, Yii::t('app', 'float', ['field' => $this->getAttributeLabel('price')]));
                return;
            } 
            
            $generalAffairItems['condition'] = intval($generalAffairItems['condition']);
            if (empty($generalAffairItems['condition']) || $generalAffairItems['condition'] < 1) {
                $this->addError($attribute, Yii::t('core', 'integer', ['field' => $this->getAttributeLabel('condition')]));
                return;
            } 
    
            if (empty($generalAffairItems['division']['id'])) {
                $this->addError($attribute, Yii::t('core', 'required', ['field' => $this->getAttributeLabel('division_id')]));
                return;
            }
    
            $division = Division::findOne(['id' => $generalAffairItems['division']['id'], 'status' => Constants::STATUS_ACTIVE]);
            if ($division === null) {
                $this->addError($attribute, Yii::t('core', 'fieldDataNotFound', ['field' => $this->getAttributeLabel('division')]));
                return;
            }

            $generalAffairItems['division'] = ['id' => $division->id, 'name' => $division->name];
            
            $dateValidator = new \yii\validators\DateValidator(['format' => 'php:Y-m-d']);
            if (isset($generalAffairItems['buying_date']) && !$dateValidator->validate($generalAffairItems['buying_date'], $error)) {
                $this->addError($attribute, Yii::t('app', 'dateInvalid', ['field' => $this->getAttributeLabel('buying_date')]));
                return;
            }
    
            if (isset($generalAffairItems['tax_date']) && !$dateValidator->validate($generalAffairItems['tax_date'], $error)) {
                $this->addError($attribute, Yii::t('app', 'dateInvalid', ['field' => $this->getAttributeLabel('tax_date')]));
                return;
            }
    
            if (isset($generalAffairItems['inspection_date']) && !$dateValidator->validate($generalAffairItems['inspection_date'], $error)) {
                $this->addError($attribute, Yii::t('app', 'dateInvalid', ['field' => $this->getAttributeLabel('inspection_date')]));
                return;
            }

            if (isset($generalAffairItems['image']) && is_array($generalAffairItems['image'])) {
                $generalAffairItems['image'] = array_filter($generalAffairItems['image'], fn($item) => !in_array($item, ['[]', '', null], true));
                $generalAffairItems['image'] = array_values($generalAffairItems['image']);
            }
            
            $oldItem['items'][$generalAffairItems['code']] = $generalAffairItems;
        }
        
        $this->$attribute = ['items' => $oldItem['items']];
    }
    
    public function calculateAsset($asset)
    {
        $filteredItems = array_filter($asset['items'], function($value) {
            return is_array($value) && array_key_exists('price', $value) && $value['items_change_log']['deleted_at'] === null;
        });

        $totalPrice = array_sum(array_map(function($value) {
            return $value['price'];
        }, $filteredItems));

        $asset['quantity'] = count($filteredItems);
        $asset['total_price'] = $totalPrice;
        
        return $asset;
    }

    public function validateAssetDeleted($attribute, $params)
    {
        $oldData = $this->getAttributes();
        $itemCodes = array_column($oldData['asset']['items'], 'code') ?? [];
        $timestamp = Yii::$app->coreAPI->UTCTimestamp();

        if (!empty($this->code) && is_array($oldData['asset']) && array_key_exists('items', $oldData['asset'])) {
            if (in_array($this->code, $itemCodes, true)) {
                $oldData['asset']['items'][$this->code]['items_change_log']['deleted_at'] = $timestamp;
                $oldData['asset']['items'][$this->code]['items_change_log']['deleted_by'] = $username;
                $this->setAttribute('asset', $oldData['asset']);
                
                return true;
            }
        }
    }

    public function validateProduct($attribute, $params)
    {
        $product = GeneralAffairProduct::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

        if ($product === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['field' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->product = ['id' => $product->id, 'name' => $product->name];
        $this->name = $product->name;
    }

    public function validateCategory($attribute, $params)
    {
        $category = GeneralAffairCategory::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

        if ($category === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['field' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->category = ['id' => $category->id, 'name' => $category->name];
    }

    public function validateLocation($attribute, $params)
    {
        $location = GeneralAffairLocation::findOne(['id' => $this->$attribute, 'status' => Constants::STATUS_ACTIVE]);

        if ($location === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['field' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->location = ['id' => $location->id, 'name' => $location->name];
    }
}