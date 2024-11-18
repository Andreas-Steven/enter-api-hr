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
    public $page;
    public $page_size;
    public $sort;
    public $sort_by;
    public $created_at;
    public $created_by;
    public $updated_at;
    public $updated_by;
    public $deleted_at;
    public $deleted_by;

    public $name;
    public $code; 
    public $min_price;
    public $max_price;
    public $date_min;
    public $date_max;

    public function rules()
    {
        return [
            [['code', 'name'], 'string'],
            [['detail_info', 'item', 'test'], 'safe'],
            [['id', 'category_id', 'location_id', 'status'], 'integer'],
            [['date_min', 'date_max'], 'date', 'format' => 'php:Y-m-d'],
            [['min_price', 'max_price'], 'filter', 'filter' => 'floatval'],
            
            [['sort', 'sort_by'], 'string'],
            [['page', 'page_size'], 'integer'],
            [['page'], function ($attribute, $params, $validator) {
                Yii::$app->coreAPI->validatePage($attribute, $params, $this);
            }],
            [['detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function getErrors($attribute = null)
    {
        return Yii::$app->coreAPI->searchGetErrors(parent::getErrors());
    }

    public function search($params)
    {

        $this->load([Yii::$app->coreAPI->modelClassName($this) => $params]);

        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($this->rules(), $params)) {
            return $unavailableParams;
        }
        
        $query = GeneralAffair::find();
        $query->where(Constants::STATUS_NOT_DELETED);     

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        
        // add conditions that should always apply here
        // grid filtering conditions
        $query->andFilterWhere(Yii::$app->coreAPI->nameFilter($this->name));
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'category_id' => $this->category_id,
            'location_id' => $this->location_id,
        ]);

        $query->andFilterWhere(Yii::$app->coreAPI->changelogDateFilters($this));
        $query->andFilterWhere(Yii::$app->coreAPI->changelogFilters($this));

        $dataProvider->setPagination(
            Yii::$app->coreAPI->setPagination($params, $dataProvider)
        );

        $dataProvider->setSort(
            Yii::$app->coreAPI->setSort($params)
        );

        // Custom Query for General_Affair.Item.Items Filter
        $query->addSelect([
            '*',
            'item' => new \yii\db\Expression("(
                SELECT jsonb_build_object(
                    'items', jsonb_object_agg(key, value)
                    " . ($this->code === null ? ",
                    'quantity', (general_affair.item->>'quantity')::integer,
                    'total_price', (general_affair.item->>'total_price')::numeric" : "") . "
                )
                FROM jsonb_each(general_affair.item->'items') AS items_record(key, value)
                WHERE value->'items_change_log'->>'deleted_at' IS NULL
                " . ($this->code !== null ? "AND key = :code" : "") . "
            )")
        ]);
    
        if ($this->code != null) {
            $query->addParams([':code' => $this->code]);
        }

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
}