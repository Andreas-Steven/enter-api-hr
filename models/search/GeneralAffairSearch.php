<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use app\helpers\Constants;
use yii\data\ActiveDataProvider;
use app\models\GeneralAffair;

/**
 * GeneralAffairSearch represents the model behind the search form of `app\models\GeneralAffair`.
 */
class GeneralAffairSearch extends GeneralAffair
{
    public $created_at;
    public $created_by;
    public $updated_at;
    public $updated_by;
    public $deleted_at;
    public $deleted_by;
    public $code;
    public $sort;
    public $sort_by;
    public $page;
    public $page_size;
    public $min_price;
    public $max_price;
    public $date_min;
    public $date_max;

    public function rules()
    {
        return [
            [['id', 'category_id', 'location_id', 'status', 'page', 'page_size'], 'integer'],
            [['detail_info', 'item'], 'safe'],
            [['code', 'sort', 'sort_by'], 'string'],
            [['detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
            [['min_price', 'max_price'], 'filter', 'filter' => 'floatval'],
            [['date_min', 'date_max'], 'date', 'format' => 'php:Y-m-d'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $this->sort = isset($params['sort']) ? $params['sort'] : 'id';
        $this->sort_by = isset($params['sort_by']) && $params['sort_by'] == 'asc' ? "SORT_ASC" : "SORT_DESC";
        $this->page_size = isset($params['page_size']) ? $params['page_size'] : 10;
        $this->code = isset($params['code']) ? $params['code'] : null;

        $query = GeneralAffair::find();
        $query->where(Constants::STATUS_NOT_DELETED);
        $query->addSelect([
            '*',
            new \yii\db\Expression("(
                SELECT jsonb_object_agg(key, value)
                FROM jsonb_each(general_affair.item->'items') AS items_record(key, value)
                WHERE value->'items_change_log'->>'deleted_at' IS NULL
            ) AS items")
        ]);

        if ($this->code != null) {
            $query
            ->select(['id', 'name', 'category_id', 'location_id', 'status', 'detail_info', new \yii\db\Expression("jsonb_build_object(key, value) AS items")])
            ->from(['general_affair', new \yii\db\Expression("jsonb_each(item->'items') AS items_record(key, value)")])
            ->andWhere(['key' => $this->code])
            ->andWhere(new \yii\db\Expression("value->'items_change_log'->>'deleted_at' IS NULL"));
        }         

        // add conditions that should always apply here
        $rules = $this->rules();
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($rules, $params)) {
            return $unavailableParams;
        }
        
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> [
                'defaultOrder' => [$this->sort => $this->sort_by],
            ],
            'pagination' => [
                'pageSize' => $this->page_size,
            ],
        ]);
        
        $this->load(['GeneralAffairSearch' => $params]);
        
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        
        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'category_id' => $this->category_id,
            'location_id' => $this->location_id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['ilike', 'name', $this->name])
        ->andFilterWhere(['ilike', 'detail_info', $this->detail_info])
        ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,created_at}'"), $this->created_at])
        ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,created_by}'"), $this->created_by])
        ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,updated_at}'"), $this->updated_at])
        ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,updated_by}'"), $this->updated_by])
        ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,deleted_at}'"), $this->deleted_at])
        ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,deleted_by}'"), $this->deleted_by]);
        
        if (isset($this->min_price) && isset($this->max_price) && !empty($this->min_price) && !empty($this->max_price)) {
            $query->andWhere(new \yii\db\Expression("(
                SELECT COUNT(*)
                FROM jsonb_each(general_affair.item->'items') AS items_record(key, value)
                WHERE (value->>'price')::numeric BETWEEN :min_price AND :max_price
                AND value->'items_change_log'->>'deleted_at' IS NULL
            ) > 0", [
                ':min_price' => $this->min_price,
                ':max_price' => $this->max_price
            ]));
        } elseif (isset($this->min_price) && !empty($this->min_price)) {
            $query->andWhere(new \yii\db\Expression("(
                SELECT COUNT(*)
                FROM jsonb_each(general_affair.item->'items') AS items_record(key, value)
                WHERE (value->>'price')::numeric >= :min_price
                AND value->'items_change_log'->>'deleted_at' IS NULL
            ) > 0", [
                ':min_price' => $this->min_price
            ]));
        } elseif (isset($this->max_price) && !empty($this->max_price)) {
            $query->andWhere(new \yii\db\Expression("(
                SELECT COUNT(*)
                FROM jsonb_each(general_affair.item->'items') AS items_record(key, value)
                WHERE (value->>'price')::numeric <= :max_price
                AND value->'items_change_log'->>'deleted_at' IS NULL
            ) > 0", [
                ':max_price' => $this->max_price
            ]));
        }

        if (isset($this->date_min) && isset($this->date_max) && !empty($this->date_min) && !empty($this->date_max)) {
            $query->andWhere(new \yii\db\Expression("(
                SELECT COUNT(*)
                FROM jsonb_each(general_affair.item->'items') AS items_record(key, value)
                WHERE (value->>'buying_date')::date BETWEEN :date_min AND :date_max
                AND value->'items_change_log'->>'deleted_at' IS NULL
            ) > 0", [
                ':date_min' => $this->date_min,
                ':date_max' => $this->date_max
            ]));
        } 
        elseif (isset($this->date_min) && !empty($this->date_min)) {
            $query->andWhere(new \yii\db\Expression("(
                SELECT COUNT(*)
                FROM jsonb_each(general_affair.item->'items') AS items_record(key, value)
                WHERE (value->>'buying_date')::date >= :date_min
                AND value->'items_change_log'->>'deleted_at' IS NULL
            ) > 0", [
                ':date_min' => $this->date_min
            ]));
        } elseif (isset($this->date_max) && !empty($this->date_max)) {
            $query->andWhere(new \yii\db\Expression("(
                SELECT COUNT(*)
                FROM jsonb_each(general_affair.item->'items') AS items_record(key, value)
                WHERE (value->>'buying_date')::date <= :date_max
                AND value->'items_change_log'->>'deleted_at' IS NULL
            ) > 0", [
                ':date_max' => $this->date_max
            ]));
        }
        
        return $dataProvider;
    }

    public function resultFormatter($dataProvider)
    {
        $formattedData = [];

        foreach ($dataProvider->getModels() as $model) {
            $quantity = 0;
            $total_price = 0;

            if (isset($model->item) && is_array($model->item)) {
                foreach ($model->item as $items) {
                    if (isset($items['price']) && $items['items_change_log']['deleted_at'] === null) {
                        $quantity++;
                        $total_price += $items['price'];
                    }
                }
            }

            $formattedData = [
                "id" => $model->id,
                "name" => $model->name,
                "category_id" => $model->category_id,
                "location_id" => $model->location_id,
                "status" => $model->status,
                "detail_info" => [
                    "change_log" => [
                        "created_at" => $model->detail_info['change_log']['created_at'] ?? null,
                        "created_by" => $model->detail_info['change_log']['created_by'] ?? null,
                        "deleted_at" => $model->detail_info['change_log']['deleted_at'] ?? null,
                        "deleted_by" => $model->detail_info['change_log']['deleted_by'] ?? null,
                        "updated_at" => $model->detail_info['change_log']['updated_at'] ?? null,
                        "updated_by" => $model->detail_info['change_log']['updated_by'] ?? null,
                    ],
                ],
                "item" => [
                    "items" => $model->item,
                    "quantity" => $quantity,
                    "total_price" => $total_price,
                ],
            ];
        }


        return $formattedData;
    }
}