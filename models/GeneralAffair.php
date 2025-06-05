<?php

namespace app\models;

use Yii;
use app\helpers\Constants;
use app\core\CoreModel;
use app\exceptions\ErrorMessage;

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
                [['asset', 'code', 'detail_info'], 'safe'],
                [['product_id', 'category_id', 'location_id'], 'integer'],
                
                [['product_id', 'category_id', 'location_id'], 'required'],
                [['product_id', 'category_id', 'location_id'], 'filter', 'filter' => 'intval'],

                [['asset'], function($attribute) {
                    CoreModel::validateAttributeArray($this, $attribute, 'Asset');
                }],
                
                [['product_id'], 'validateProduct'],
                [['category_id'], 'validateCategory'],
                [['location_id'], 'validateLocation'],
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

        unset($fields['sync'], $fields['sync_mdb']);

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
            if ($this->scenario === Constants::SCENARIO_CREATE && $this->isAttributeChanged('asset')) {
                $this->validateAsset('asset');
            }

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

    public function validateAsset($attribute)
    {
        $generalAffairItems = [];
        $itemData = $this->$attribute;
        $oldItem = $this->getOldAttribute($attribute);

        foreach ($itemData['items'] as $key => $value) {
            $timestamp = Yii::$app->coreAPI->UTCTimestamp();
            $username = Yii::$app->coreAPI->getUsername();

            if (empty($value['code'])) {
                $this->addError($attribute, Yii::t('app', 'null', ['label' => 'Code']));
            }
            
            if (!is_string($value['code']) || strlen($value['code']) < 10 || strlen($value['code']) > 25) {
                $this->addError($attribute, Yii::t('app', 'codeFormatInvalid'));
            }

            $allowedFields = Yii::$app->params['allowedFields']['items'];
            CoreModel::validateRequiredFields($this, $attribute, $allowedFields, $value);

            if (isset($oldItem['items'][$value['code']])) {
                $generalAffairItems = array_merge($oldItem['items'][$value['code']], $value);
                $generalAffairItems['items_change_log']['updated_at'] = $timestamp;
                $generalAffairItems['items_change_log']['updated_by'] = $username;
            } else {
                $generalAffairItems = array_merge(Yii::$app->params['defaultValue']['items'], $value);
                $generalAffairItems['items_change_log']['created_at'] = $timestamp;
                $generalAffairItems['items_change_log']['created_by'] = $username;
            }

            if (!ctype_digit((string) $generalAffairItems['price'])) {
                $this->addError($attribute, Yii::t('app', 'float', ['label' => 'Price']));
            }
            $generalAffairItems['price'] = floatval($generalAffairItems['price']);  

            if (!filter_var($generalAffairItems['condition'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
                $this->addError($attribute, Yii::t('app', 'integer', ['label' => 'Condition']));
            }

            $generalAffairItems['condition'] = intval($generalAffairItems['condition']);
            if (!in_array($generalAffairItems['condition'], array_keys(Constants::ASSET_CONDITION))) {
                $this->addError($attribute, Yii::t('app', 'invalidCondition', ['label' => 'Condition']));
            }
        
            if (!filter_var($generalAffairItems['employee']['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
                $this->addError($attribute, Yii::t('app', 'integer', ['label' => 'Employee']));
            }
            $generalAffairItems['employee']['id'] = intval($generalAffairItems['employee']['id']);
            $employee = Employee::findOne(['id' => $generalAffairItems['employee']['id'], 'status' => Constants::STATUS_ACTIVE]);
            if ($employee === null) {
                $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => 'Employee']));
            }

            if (!filter_var($generalAffairItems['division']['id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]])) {
                $this->addError($attribute, Yii::t('app', 'integer', ['label' => 'Division']));
            }
            
            $generalAffairItems['division']['id'] = intval($generalAffairItems['division']['id']);
            $division = Division::findOne(['id' => $generalAffairItems['division']['id'], 'status' => Constants::STATUS_ACTIVE]);
            if ($division === null) {
                $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => 'Division']));
            }

            // DATE VALIDATION
            $dateValidator = new \yii\validators\DateValidator(['format' => 'php:Y-m-d']);
            $generalAffairItems['buying_date'] = CoreModel::nullSafe($generalAffairItems['buying_date']);
            if (empty($generalAffairItems['buying_date'])) {
                $this->addError($attribute, Yii::t('app', 'required', ['label' => 'Buying Date']));
            }

            if (!$dateValidator->validate($generalAffairItems['buying_date'], $error)) {
                $this->addError($attribute, Yii::t('app', 'dateInvalid', ['label' => 'Buying Date']));
            }
            
            $generalAffairItems['tax_date'] = CoreModel::nullSafe($generalAffairItems['tax_date']);
            if (!empty($generalAffairItems['tax_date']) && !$dateValidator->validate($generalAffairItems['tax_date'], $error)) {
                $this->addError($attribute, Yii::t('app', 'dateInvalid', ['label' => 'Tax Date']));
            }
            
            $generalAffairItems['inspection_date'] = CoreModel::nullSafe($generalAffairItems['inspection_date']);
            if (!empty($generalAffairItems['inspection_date']) && !$dateValidator->validate($generalAffairItems['inspection_date'], $error)) {
                $this->addError($attribute, Yii::t('app', 'dateInvalid', ['label' => 'Inspection Date']));
            }
            // DATE VALIDATION END

            // IMAGE VALIDATION
            if (!empty($generalAffairItems['image'])) {
                $generalAffairItems['image'] = array_filter($generalAffairItems['image'], function($item) {
                    if (in_array($item, ['[]', '', 'null', null], true)) {
                        return false;
                    }

                    if (!filter_var($item, FILTER_VALIDATE_URL)) {
                        $this->addError('image', Yii::t('app', 'invalidImage', ['label' => 'Image']));
                        return false;
                    }

                    return true;
                });

                $generalAffairItems['image'] = array_values($generalAffairItems['image']);
            }
            // IMAGE VALIDATION END
            
            if ($this->hasErrors()) {
                throw new ErrorMessage($this, Yii::t('app', 'generalAffairFailed'), 422);
            }
            
            $generalAffairItems['employee'] = ['id' => $employee->id, 'name' => $employee->name];
            $generalAffairItems['division'] = ['id' => $division->id, 'name' => $division->name];

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
        $product = GeneralAffairProduct::findOne([
            'id' => $this->$attribute, 
            'status' => Constants::STATUS_ACTIVE
        ]);

        if ($product === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->product = [
            'id' => $product->id, 
            'name' => $product->name,
        ];
        $this->name = $product->name;
    }

    public function validateCategory($attribute, $params)
    {
        $category = GeneralAffairCategory::findOne([
            'id' => $this->$attribute, 
            'status' => Constants::STATUS_ACTIVE
        ]);

        if ($category === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->category = [
            'id' => $category->id, 
            'name' => $category->name,
        ];
    }

    public function validateLocation($attribute, $params)
    {
        $location = GeneralAffairLocation::findOne([
            'id' => $this->$attribute, 
            'status' => Constants::STATUS_ACTIVE
        ]);

        if ($location === null) {
            $this->addError($attribute, Yii::t('app', 'fieldDataNotFound', ['label' => $this->getAttributeLabel($attribute)]));
            return;
        }

        $this->location = [
            'id' => $location->id, 
            'name' => $location->name,
        ];
    }
}