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
 * @property Department $department
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
        return [
            [['name'], 'string', 'max' => 255],
            [['category_id', 'location_id', 'status'], 'integer'],
            [['name', 'category_id', 'location_id'], 'required'],
            [['detail_info', 'item'], 'safe', 'skipOnEmpty' => true],
            
            [['status'], 'default', 'value' => 2],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],

            [['item', 'code'], 'safe'],
            [['item'], 'validateItem'],

            [['category_id', 'location_id', 'status'], 'filter', 'filter' => 'intval'],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => GeneralAffairCategory::class, 'targetAttribute' => ['category_id' => 'id']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => GeneralAffairLocation::class, 'targetAttribute' => ['location_id' => 'id']],
            
            [['code', 'status'], 'validateDeleted', 'on' => Constants::SCENARIO_DELETE],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'category_id', 'location_id', 'item', 'detail_info'];
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
        $defaultItems = Yii::$app->params['items'];

        foreach ($itemData['items'] as $key => $value) {
            $generalAffairItems = $defaultItems;
            
            if (!isset($value['code']) || empty($value['code'])) {
                $this->addError($attribute, 'Item code cannot be empty');
                return;
            } 

            if (!isset($value['price']) || empty($value['price']) || $value['price'] < 0) {
                $this->addError($attribute, 'Price value must be a float greater than or equal to 0');
                return;
            } 

            $value['price'] = floatval($value['price']);

            if (!isset($value['condition']) || empty($value['condition']) || $value['price'] < 1) {
                $this->addError($attribute, 'Condition value must be an integer greater than 0');
                return;
            } 

            $value['condition'] = intval($value['condition']);

            if (isset($value['department']['id'])) {
                $value['department']['id'] = intval($value['department']['id']);
                if (!is_integer($value['department']['id']) || $value['department']['id'] < 1 || empty($value['condition']) ) {
                    $this->addError($attribute, 'Items department id value must be an integer greater than 0');
                    return;
                }
                
                $department = Department::find()->where([
                    'id' => $value['department']['id'],
                    'status' => Constants::STATUS_ACTIVE
                ])->one();
                    
                if ($department === null) {
                    $this->addError($attribute, 'Department not found');
                    return;
                }
                
                $generalAffairItems['department'] = ['id' => $department->id, 'name' => $department->name];
            } elseif (!isset($value['department']['id'])) {
                $this->addError($attribute, 'Items department cannot be empty');
                return;
            }

            if (isset($value['description'])) {
                if (empty($value['description'])) {
                    $value['description'] = null;
                } 
            }

            if (isset($value['image'])) {
                $value['image'] = array_filter($value['image'], function($image) {
                    return !empty($image);
                });
            }

            if (isset($value['tax_date'])) {
                if (empty($value['tax_date'])) {
                    $value['tax_date'] = null;
                } 
            }

            if (isset($value['inspection_date'])) {
                if (empty($value['inspection_date'])) {
                    $value['inspection_date'] = null;
                } 
            }

            $generalAffairItems['code'] = $value['code'] ?? null;
            $generalAffairItems['price'] = $value['price'] ?? null;
            $generalAffairItems['condition'] = $value['condition'] ?? null;
            $generalAffairItems['buying_date'] = $value['buying_date'] ?? null;
            $generalAffairItems['inspection_date'] = $value['inspection_date'] ?? null;
            $generalAffairItems['tax_date'] = $value['tax_date'] ?? null;
            $generalAffairItems['description'] = $value['description'] ?? null; 
            $generalAffairItems['image'] = $value['image'] ?? [];
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
            // $itemsIndex = array_search($this->code, $itemCodes);
            $timestamp = Yii::$app->coreAPI->timestamp();

            if ($this->code !== false) {
                $item_change_log = $this->itemChangeLog($oldItem['item']['items'][$this->code], 'delete');
                $oldItem['item']['items'][$this->code]['items_change_log'] = $item_change_log;
                
                $this->setAttribute('item', $oldItem['item']);
                return true;
            }
        } elseif (isset($this->status) && !empty($this->status) && $this->status === Constants::STATUS_DELETED) {
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
                'created_by' => (is_null($username) ? 'System' : $username), 
                'updated_at' => null,
                'updated_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
            ];
        } elseif ($type == 'update') {
            $generalAffairItems['items_change_log']['updated_at'] = $timestamp;
            $generalAffairItems['items_change_log']['updated_by'] = (is_null($username) ? 'System' : $username);
        } elseif ($type == 'delete') {
            // var_dump($generalAffairItems['items_change_l     og']); exit;
            $generalAffairItems['items_change_log']['deleted_at'] = $timestamp;
            $generalAffairItems['items_change_log']['deleted_by'] = (is_null($username) ? 'System' : $username);
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
        $this->name = Yii::$app->coreAPI->htmlPurifier($this->name);
        $this->item = $this->calculateItems($this->item);
        $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
        $this->detail_info = [
            'change_log' => $this->change_log ?: null,
        ];

        return parent::beforeSave($insert);
    }
}