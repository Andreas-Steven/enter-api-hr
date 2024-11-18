<?php

namespace app\models;

use Yii;
use app\helpers\Constants;

/**
 * This is the model class for table "general_affair".
 *
 * @property int $id
 * @property string $name
 * @property int $category_id
 * @property int $location_id
 * @property int $status
 * @property string $detail_info
 * @property string $item
 *
 * @property GeneralAffairCategory $category
 * @property GeneralAffairLocation $location
 * @property Division $divison
 */
class GeneralAffair extends \yii\db\ActiveRecord
{
    public $change_log;
    public $code;

    public static function tableName()
    {
        return 'general_affair';
    }
    
    public function rules()
    {
        return array_merge(
            [
                [['item', 'code'], 'safe'],
                [['name'], 'string', 'max' => 255],
                [['category_id', 'location_id'], 'integer'],
                [['name', 'category_id', 'location_id'], 'required'],
                [['detail_info', 'item'], 'safe', 'skipOnEmpty' => true],
                [['category_id', 'location_id'], 'filter', 'filter' => 'intval'],
                
                [['item'], 'validateItem', 'on' => Constants::SCENARIO_CREATE],
                [['code', 'status'], 'validateDeleted', 'on' => Constants::SCENARIO_DELETE],
            ],
            Yii::$app->coreAPI->statusRules($this)
        );
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'category_id', 'location_id', 'item', 'status',  'detail_info'];
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
            'item' => 'Item',
        ];
    }

    public static function find()
    {
        return new \app\models\query\GeneralAffairQuery(get_called_class());
    }

    public function validateItem($attribute, $params)
    {
        if (!is_array($this->$attribute)) {
            $this->addError($attribute, 'Item must be an array');
            return;
        } 

        $generalAffairItems = [];
        $itemData = $this->$attribute;
        $oldItem = $this->getOldAttribute($attribute);
        $itemCodes = $oldItem ? array_column($oldItem['items'], 'code') : [];

        foreach ($itemData['items'] as $key => $value) {
            $generalAffairItems = Yii::$app->params['items'];
            $generalAffairItems = array_merge($generalAffairItems, $value);
            
            if (empty($generalAffairItems['code'])) {
                $this->addError($attribute, 'Item code cannot be empty');
                return;
            } 

            if (empty($generalAffairItems['price']) || $generalAffairItems['price'] < 0) {
                $this->addError($attribute, 'Price value must be a float greater than or equal to 0');
                return;
            } 
            
            if (empty($generalAffairItems['condition']) || $generalAffairItems['condition'] < 1) {
                $this->addError($attribute, 'Condition value must be an integer greater than 0');
                return;
            } 

            if (empty($generalAffairItems['division']['id'])) {
                $this->addError($attribute, 'Items divison cannot be empty');
                return;
            }

            $division = Division::find()->where([
                'id' => $generalAffairItems['division']['id'], 
                'status' => Constants::STATUS_ACTIVE
            ])->one();

            if ($division === null) {
                $this->addError($attribute.'['.$key.'].items', 'Division not found');
                return;
            }

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

            $generalAffairItems['code'] = $generalAffairItems['code'];
            $generalAffairItems['price'] = floatval($generalAffairItems['price']);
            $generalAffairItems['condition'] = intval($generalAffairItems['condition']);
            $generalAffairItems['buying_date'] = $generalAffairItems['buying_date'];
            $generalAffairItems['inspection_date'] = $generalAffairItems['inspection_date'];
            $generalAffairItems['tax_date'] = $generalAffairItems['tax_date'];
            $generalAffairItems['description'] = $generalAffairItems['description']; 
            $generalAffairItems['image'] = $generalAffairItems['image'];
            $generalAffairItems['division'] = ['id' => $division->id, 'name' => $division->name];
            $generalAffairItems['items_change_log'] = $this->itemChangeLog($generalAffairItems, 'cerate');

            if (isset($this->id)) {
                if (is_array($oldItem['items'])) {
                    $itemsIndex = array_search($generalAffairItems['code'], $itemCodes);
                    
                    if ($itemsIndex !== false) {
                        $generalAffairItems['items_change_log'] = $this->itemChangeLog($generalAffairItems, 'update');
                        $oldItem['items'][$generalAffairItems['code']] = $generalAffairItems; 
                    } else {
                        $oldItem['items'] = array_merge($oldItem['items'], [$generalAffairItems['code'] => $generalAffairItems]);
                    }
                }
            } else {
                $oldItem['items'][$generalAffairItems['code']] = $generalAffairItems;        
            }
        }
        
        $this->$attribute = $oldItem;
    }

    public function validateDeleted($attribute, $params, $validator)
    {
        $oldItem = $this->attributes;
        $itemCodes = $oldItem && array_key_exists('items', $oldItem['item']) ? array_column($oldItem['item']['items'], 'code') : [];
        
        if (!empty($this->code) && is_array($oldItem['item']) && array_key_exists('items', $oldItem['item'])) { 
            $timestamp = Yii::$app->coreAPI->timestamp();

            if ($this->code !== false) {
                $item_change_log = $this->itemChangeLog($oldItem['item']['items'][$this->code], 'delete');
                $oldItem['item']['items'][$this->code]['items_change_log'] = $item_change_log;
                
                $this->setAttribute('item', $oldItem['item']);
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

    public function itemChangeLog($generalAffairItems, $type)
	{
		$timestamp = Yii::$app->coreAPI->timestamp();
		$username = Yii::$app->session->get('username');

        if (!isset($generalAffairItems['items_change_log']) || empty($generalAffairItems['items_change_log']) && $type == 'cerate') {
            $generalAffairItems['items_change_log'] = [
                'created_at' => $timestamp,
                'created_by' => (is_null($username) ? 'Admin' : $username), 
                'updated_at' => null,
                'updated_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
            ];
        } elseif ($type == 'update') {
            $generalAffairItems['items_change_log']['updated_at'] = $timestamp;
            $generalAffairItems['items_change_log']['updated_by'] = (is_null($username) ? 'Admin' : $username);
        } elseif ($type == 'delete') {
            $generalAffairItems['items_change_log']['deleted_at'] = $timestamp;
            $generalAffairItems['items_change_log']['deleted_by'] = (is_null($username) ? 'Admin' : $username);
        }
		
		return $generalAffairItems['items_change_log'];
	}

    public function calculateItems($item)
    {
        $quantity = $total_price = $index = 0;
		
		foreach ($item['items'] as $key => $value) {
			if (is_array($value) && array_key_exists('price', $value) && $value['items_change_log']['deleted_at'] === null) {
				$total_price += $value['price'];
				$quantity++;
			}
		}

		$item['quantity'] = $quantity;
		$item['total_price'] = $total_price;

		return $item;
    }

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {
            $this->name = Yii::$app->coreAPI->htmlPurifier($this->name);
            $this->item = $this->calculateItems($this->item);
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log,
            ];

            return true;
        }

        return false;
    }
}