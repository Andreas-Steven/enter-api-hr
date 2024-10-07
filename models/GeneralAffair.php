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
 * @property GeneralAffairDepartment $department
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
            [['name', 'category_id', 'location_id'], 'required'],
            [['status'], 'default', 'value' => 2],
            [['category_id', 'location_id', 'status'], 'integer'],
            [['detail_info', 'item'], 'safe'],
            [['name'], 'string', 'max' => 255],
            [['category_id'], 'exist', 'skipOnError' => true, 'targetClass' => GeneralAffairCategory::class, 'targetAttribute' => ['category_id' => 'id']],
            [['location_id'], 'exist', 'skipOnError' => true, 'targetClass' => GeneralAffairLocation::class, 'targetAttribute' => ['location_id' => 'id']],
            [['status'], 'in', 'range' => array_keys(Constants::statusList())],
            [['category_id', 'location_id', 'status'], 'filter', 'filter' => 'intval'],
            
        ];
    }

    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios[Constants::SCENARIO_CREATE] = ['name', 'category_id', 'location_id', 'item'];
        $scenarios[Constants::SCENARIO_UPDATE] = ['id', 'item'];
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
        ];
    }

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
        return $this->hasOne(GeneralAffairDepartment::class, ['id' => 'department_id']);
    }

    public function getDepartmentNameById($id)
    {
        $department = GeneralAffairDepartment::findOne($id);
        return $department ? $department->name : null;
    }

    public static function find()
    {
        return new \app\models\query\GeneralAffairQuery(get_called_class());
    }

    public function beforeSave($insert)
    {
        if ($insert) {
            $this->item = Yii::$app->coreAPI->calculateValue($this->item, $insert);
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];
        } else {
            $this->item = Yii::$app->coreAPI->calculateValue($this->item, $insert);
            $this->change_log = Yii::$app->coreAPI->changeLog($this, $insert);
            $this->detail_info = [
                'change_log' => $this->change_log ?: null,
            ];
        }

        return parent::beforeSave($insert);
    }

    public function load($value, $formName = null)
    {
        if (parent::load($value, $formName) && isset($value)) {
            $departmentName = null;
            if (isset($value['item']['items']['department']['id'])) {
                $departmentName = $this->getDepartmentNameById($value['item']['items']['department']['id']);
            }

            $price = null;
            if (!empty($value['item']['items']['price'])) {
                $price = floatval($value['item']['items']['price']);
            }
            
            $this->name                 = !empty($value['name'])        ? $value['name']                : 'system';
            $this->category_id          = !empty($value['category_id']) ? (int) $value['category_id']   : 'system';
            $this->location_id          = !empty($value['location_id']) ? (int) $value['location_id']   : 'system';
            $this->status               = 2;
            $this->item = [
                'items' => [
                    '0' => [
                        'code'              => !empty($value['item']['items']['code'])            ? $value['item']['items']['code']        : null, 
                        'image'             => [],
                        'price'             => $price,
                        'condition'         => !empty($value['item']['items']['condition'])        ? (int) $value['item']['items']['condition']  : null,
                        'buying_date'       => !empty($value['item']['items']['buying_date'])      ? $value['item']['items']['buying_date']      : null,
                        'inspection_date'   => !empty($value['item']['items']['inspection_date'])  ? $value['item']['items']['inspection_date']  : null,
                        'tax_date'          => !empty($value['item']['items']['tax_date'])         ? $value['item']['items']['tax_date']         : null,
                        'description'       => !empty($value['item']['items']['description'])      ? $value['item']['items']['description']      : null,
                        'department' => [ 
                            'id'            => !empty($value['item']['items']['department']['id']) ? (int) $value['item']['items']['department']['id'] : null,
                            'name'          => $departmentName,
                        ],
                        'status'            => 2,
                        'item_change_log' => [
                            'created_at'    => Yii::$app->coreAPI->timestamp(),
                            'created_by'    => Yii::$app->session->get('username'),
                            'deleted_at'    => null,
                            'deleted_by'    => null,
                            'updated_at'    => null,
                            'updated_by'    => null,
                        ]
                    ]
                ]
            ];

            if ($this->save()) {
                $response = [
                    'name' => $this->name,
                    'category_id' => $this->category_id,
                    'location_id' => $this->location_id,
                    'status' => $this->status,
                    'detail_info' => $this->detail_info,
                    'item' => [
                        'items' => $this->item,

                    ]
                ];
            
                return $response;
            }
        }

        return false;        
    }

    public function upsert($value, $formName = null)
    {
        if ($value['id'] != null) {
            $existing = self::find()->where(['id' => $value['id']])->one();
            $newIndex = Yii::$app->coreAPI->getNewIndex($existing);

            $newItem[$newIndex] = [
                'code'              => $value['item']['items']['code'] == "" ? null : $value['item']['items']['code'],
                'image'             => [],
                'price'             => floatval($value['item']['items']['price']),
                'condition'         => $value['item']['items']['condition'] == "" ? null : (int) $value['item']['items']['condition'],
                'buying_date'       => $value['item']['items']['buying_date'] == "" ? null : $value['item']['items']['buying_date'],
                'inspection_date'   => $value['item']['items']['inspection_date'] == "" ? null : $value['item']['items']['inspection_date'],
                'tax_date'          => $value['item']['items']['tax_date'] == "" ? null : $value['item']['items']['tax_date'],
                'description'       => $value['item']['items']['description'] == "" ? null : $value['item']['items']['description'],
                'department' => [
                    'id'            => $value['item']['items']['department']['id'] == "" ? null : (int) $value['item']['items']['department']['id'],
                    'name'          => $this->getDepartmentNameById($value['item']['items']['department']['id']),
                ],
                'status'            => 2,
                'item_change_log' => [
                    'created_at'    => Yii::$app->coreAPI->timestamp(),
                    'created_by'    => Yii::$app->session->get('username'),
                    'deleted_at'    => null,
                    'deleted_by'    => null,
                    'updated_by'    => null,
                ]
            ];

            $newData = array_merge($existing->item['items'], $newItem);
            $count = Yii::$app->coreAPI->calculateNewData($newData);
            $newData['quantity'] = $count['quantity'];
            $newData['total_price'] = $count['total_price'];

            $existing->updateAttributes([
                'item' => [
                    'items' => $newData,
                ],
            ]);
            $existing->save();

            return $existing;
        }
    }

    public function updateData($params)
    {
        $items = $this->item['items'];
        $code = $params['item']['items']['code'];

        $index = Yii::$app->coreAPI->findIndex($code, $items);
        
        if (isset($items[$index]) && $index != null) {
            $items[$index] = [
                'code'              => $params['item']['items']['code'],
                'image'             => [],
                'price'             => $params['item']['items']['price'],
                'condition'         => $params['item']['items']['condition'],
                'buying_date'       => $params['item']['items']['buying_date'],
                'inspection_date'   => $params['item']['items']['inspection_date'],
                'tax_date'          => $params['item']['items']['tax_date'],
                'description'       => $params['item']['items']['description'],
                'status'            => 2,
                'department' => [
                    'id'            => $items[$index]['department']['id'],
                    'name'          => $items[$index]['department']['name']
                ],
                'item_change_log' => [
                    'created_at'    => $items[$index]['item_change_log']['created_at'],
                    'created_by'    => $items[$index]['item_change_log']['created_by'],
                    'deleted_at'    => ($items[$index]['item_change_log']['deleted_at'] == null) ? '' : $items[$index]['item_change_log']['deleted_at'],
                    'deleted_by'    => ($items[$index]['item_change_log']['deleted_by'] == null) ? '' : $items[$index]['item_change_log']['deleted_by'],
                    'updated_at'    => Yii::$app->coreAPI->timestamp(),
                    'updated_by'    => Yii::$app->session->get('username'),
                ]
            ];  
    
            $this->updateAttributes([
                'item' => [
                    'items' => $items,
                ]
            ]);
    
            return $this->save();
        }

        return false;
    }

    public function validateItemStatusChange($model, $param)
    {
        $item = $model->item;
    
        foreach ($item['items'] as $key => $value) {
            if (is_array($value) && array_key_exists('code', $value) && $value['code'] === $param['code']) {
                $item['items'][$key]['status'] = Constants::STATUS_DELETED;
                $item['items'][$key]['item_change_log']['deleted_at'] = Yii::$app->coreAPI->timestamp();
                $item['items'][$key]['item_change_log']['deleted_by'] = Yii::$app->session->get('username');
                break;
            }
        }
    
        $model->item = Yii::$app->coreAPI->calculateValue($item, false);
    
        return $model->updateAttributes([
            'item' => $model->item
        ]);
    }

}

