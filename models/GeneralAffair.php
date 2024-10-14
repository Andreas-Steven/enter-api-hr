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
            [['name', 'category_id', 'location_id', 'item'], 'required'],
            [['detail_info', 'item'], 'safe'],
            [['name'], 'string', 'max' => 255],
            
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => GeneralAffairCategory::class, 'targetAttribute' => ['category_id' => 'id']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => GeneralAffairLocation::class, 'targetAttribute' => ['location_id' => 'id']],
            
            [['status'], 'default', 'value' => 2],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
            [['category_id', 'location_id', 'status'], 'filter', 'filter' => 'intval'],
            
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'category_id', 'location_id', 'item', 'detail_info'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['id', 'item', 'detail_info'];
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

    // Main Function Section
    public function beforeSave($insert)
    {
        if ($insert && isset($this->item)) {
            $this->item = Yii::$app->coreAPI->insertItem($this->item['items'], $this);
        }
        $this->item = Yii::$app->coreAPI->calculateValue($this->item, $insert);
        $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
        $this->detail_info = [
            'change_log' => $this->change_log ?: null,
        ];

        return parent::beforeSave($insert);
    }

    public function updateInsertItems($params, $existing)
    {
        if (isset($existing)) {
            $existing->refresh();
            $newItems = Yii::$app->coreAPI->insertItem($params['item']['items'], $existing);
            $newData = array_merge($existing->item['items'], $newItems['items']);

            $existing->updateAttributes([
                'item' => [
                    'items' => $newData,
                ],
            ]);
            $existing->save();
        
            return $existing;
        }

    }

    public function updateItems($params)
    {
        $items = $this->item['items'];
        $code = $params['item']['items']['code'];
        $index = Yii::$app->coreAPI->findIndex($code, $items);
        
        if ($index !== null) {

            foreach ($params['item']['items'] as $key => $value) {
                if ($value === "") {
                    $params['item']['items'][$key] = null;
                }
                $items[$index][$key] = $params['item']['items'][$key];
            }
            
            $items[$index] = Yii::$app->coreAPI->sanitizeItemData($items[$index]);
            $items[$index] = Yii::$app->coreAPI->itemChangeLog($items[$index]);

            $this->updateAttributes([
                'item' => [
                    'items' => $items,
                ],
            ]);
        
            return $this->save();
        }

        return false;
    }

    public function deleteItems($params)
    {
        $items = $this->item['items'];
        $code = $params['item']['items']['code'];
        $index = Yii::$app->coreAPI->findIndex($code, $items);

        if ($index !== null) {
            $items[$index]['status'] = Constants::STATUS_DELETED;
            $items[$index] = Yii::$app->coreAPI->itemChangeLog($items[$index]);

            $this->updateAttributes([
                'item' => [
                    'items' => $items,
                ],
            ]);
    
            return $this->save();
        }   
        
        return false;
    }

    // Database Relation Section
    public function getDepartmentName($id)
    {
        $department = Department::findOne($id);
        return $department ? $department->name : null;
    }

    // Database Relation Section
    public function getCategory()
    {
        return $this->hasOne(GeneralAffairCategory::class, ['id' => 'category_id']);
    }

    public function getLocation()
    {
        return $this->hasOne(GeneralAffairLocation::class, ['id' => 'location_id']);
    }

    public function getDepartment()
    {
        return $this->hasOne(Department::class, ['id' => 'department_id']);
    }
}