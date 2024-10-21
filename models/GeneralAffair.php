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

    public static function tableName()
    {
        return 'general_affair';
    }
    
    public function rules()
    {
        return [
            [['category_id', 'location_id', 'status'], 'integer'],
            [['name', 'category_id', 'location_id'], 'required'],
            [['detail_info', 'item'], 'safe', 'skipOnEmpty' => true],
            [['name'], 'string', 'max' => 255],

            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => GeneralAffairCategory::class, 'targetAttribute' => ['category_id' => 'id']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => GeneralAffairLocation::class, 'targetAttribute' => ['location_id' => 'id']],
            
            [['status'], 'default', 'value' => 2],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
            [['status'], 'validateStatusChange', 'on' => Constants::scenarioUpdate()],
            [['category_id', 'location_id', 'status'], 'filter', 'filter' => 'intval'],
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'category_id', 'location_id', 'item', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['id', 'status', 'item', 'detail_info'];
        $scenarios[Constants::SCENARIO_DELETE] = ['status'];

        return $scenarios;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'category_id' => 'Category ID',
            'location_id' => 'Location ID',
            'item' => 'Item',
            'detail_info' => 'Detail Info',
        ];
    }

    public static function find()
    {
        return new \app\models\query\GeneralAffairQuery(get_called_class());
    }

    public function validateStatusChange($attribute, $params, $validator)
    {
        if (!$this->isNewRecord && $this->isAttributeChanged($attribute)) {
            $oldStatus = $this->getOldAttribute($attribute);
            $newStatus = $this->$attribute;

            if ($oldStatus == Constants::STATUS_DELETED && $newStatus != Constants::STATUS_DELETED && !Yii::$app->coreAPI->superAdmin()) {
                $this->addError($attribute, Yii::t('app', 'deletedStatusChange', ['status' => Constants::statusList()[Constants::STATUS_DELETED]]));
            }
        }
    }
    
    public function getDepartmentName($id)
    {
        $department = Department::findOne($id);
        return $department ? $department->name : null;
    }

    public function beforeSave($insert)
    {
        if ($insert && isset($this->item)) {
            $this->item = Yii::$app->GeneralAffairCoreAPI->insertItem($this->item['items'], $this);
            $this->item = Yii::$app->GeneralAffairCoreAPI->calculateValue($this->item, $insert);
        } elseif ($insert) {
            $this->item = [];
        }
        
        $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
        $this->detail_info = [
            'change_log' => $this->change_log ?: null,
        ];

        return parent::beforeSave($insert);
    }
    
    public function updateInsertItems($params, $existing)
    {
        if (isset($existing)) {
            if (isset($params['item']['items'])) {
                $newItems = Yii::$app->GeneralAffairCoreAPI->insertItem($params['item']['items'], $existing);

                if (!empty($existing->item)) {
                    $newData = array_merge($existing->item['items'], $newItems['items']);
                } else {
                    $newData = $newItems['items'];
                }
    
                $existing->updateAttributes(['item' => ['items' => $newData]]);
                $existing->save();
                return Yii::$app->GeneralAffairCoreAPI->resultFormatter($existing, $newItems);
            }
        }

        return false;
    }

    public function updateItems($params)
    {        
        $items = $this->item;
        $code = $params['item']['items']['code'];
        $index = Yii::$app->GeneralAffairCoreAPI->findIndex($code, $items);

        if ($index === null) {
            return null;
        }
        
        $params['item']['items'] = Yii::$app->coreAPI->sanitizeEmptyFields($params['item']['items']);
        foreach ($params['item']['items'] as $key => $value) {
            $items['items'][$index][$key] = $value;
        }
        $items['items'][$index] = Yii::$app->coreAPI->sanitizeData($items['items'][$index], 'item');
        $items['items'][$index] = Yii::$app->GeneralAffairCoreAPI->itemChangeLog($items['items'][$index]);
        
        $this->updateAttributes(['item' => $items]);
        $this->save();

        return Yii::$app->GeneralAffairCoreAPI->resultFormatter($this, $items['items'][$index]);
    }

    public function deleteItems($params)
    {
        $items = $this->item['items'];
        $code = $params['item']['items']['code'];
        $index = Yii::$app->coreAPI->findIndex($code, $items);

        if ($index !== null) {
            $items[$index]['status'] = Constants::STATUS_DELETED;
            $items[$index] = Yii::$app->GeneralAffairCoreAPI->itemChangeLog($items[$index]);
            
            $this->updateAttributes(['item' => ['items' => $items]]);
            $this->save();
            return Yii::$app->GeneralAffairCoreAPI->resultFormatter($this, $items[$index]);
        }   
        
        return false;
    }
}