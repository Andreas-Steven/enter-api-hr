<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\helpers\Constants;
use app\core\CoreModel;
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
        return array_merge(
            [
                [['code', 'name'], 'string'],
                [['id', 'category_id', 'location_id', 'status'], 'integer'],
                [['date_min', 'date_max'], 'date', 'format' => 'php:Y-m-d'],
                [['min_price', 'max_price'], 'filter', 'filter' => 'floatval'],
                [['asset', 'detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
            ], 
            CoreModel::getPaginationRules($this)
        );
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function getErrors($attribute = null)
    {
        return CoreModel::getErrors(parent::getErrors());
    }

    public function search($params)
    {

        $this->load([CoreModel::getModelClassName($this) => $params]);

        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($this, $params)) {
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
        $query->andFilterWhere(CoreModel::setLikeFilter($this->name, 'name'));
        
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'category_id' => $this->category_id,
            'location_id' => $this->location_id,
        ]);

        $query->andFilterWhere(CoreModel::setChangelogFilters($this));

        // Filter for code and deleted_at
        if (!empty($this->code)) {
            $query->select([
                    'id', 'name', 'category_id', 'location_id', 'status', 'detail_info',
                    'asset' => new \yii\db\Expression("jsonb_build_object('items', jsonb_object_agg(key, value))")
                ])
                ->from(['general_affair', new \yii\db\Expression("jsonb_each(asset->'items') AS items_record(key, value)")])
                ->andWhere(['key' => $this->code])
                ->andWhere(new \yii\db\Expression("value->'items_change_log'->>'deleted_at' IS NULL"))
                ->groupBy(['id', 'name', 'category_id', 'location_id', 'status', 'detail_info']);
        } else {
            $query->addSelect([
                '*',
                'asset' => new \yii\db\Expression("(
                    SELECT jsonb_build_object(
                        'items', jsonb_object_agg(key, value),
                        'quantity', (general_affair.asset->>'quantity')::integer,
                        'total_price', (general_affair.asset->>'total_price')::numeric
                    )
                    FROM jsonb_each(general_affair.asset->'items') AS items_record(key, value)
                    WHERE value->'items_change_log'->>'deleted_at' IS NULL
                )")
            ]);
        }

        // Filter for price range
        $priceConditions = [];
        !empty($this->min_price) ? $priceConditions[] = "(value->>'price')::numeric >= :min_price" : null;
        !empty($this->max_price) ? $priceConditions[] = "(value->>'price')::numeric <= :max_price" : null;
        if ($priceConditions) {
            $query->andWhere(new \yii\db\Expression("(
                SELECT COUNT(*)
                FROM jsonb_each(general_affair.asset->'items') AS items_record(key, value)
                WHERE " . implode(' AND ', $priceConditions) . "
                AND value->'items_change_log'->>'deleted_at' IS NULL
            ) > 0"));
            !empty($this->min_price) ? $query->addParams([':min_price' => $this->min_price]) : null;
            !empty($this->max_price) ? $query->addParams([':max_price' => $this->max_price]) : null;
        }

        // Filter for date range
        $dateConditions = [];
        !empty($this->date_min) ? $dateConditions[] = "(value->>'buying_date')::date >= :date_min" : null;
        !empty($this->date_max) ? $dateConditions[] = "(value->>'buying_date')::date <= :date_max" : null;
        if ($dateConditions) {
            $query->andWhere(new \yii\db\Expression("(
                SELECT COUNT(*)
                FROM jsonb_each(general_affair.asset->'items') AS items_record(key, value)
                WHERE " . implode(' AND ', $dateConditions) . "
                AND value->'items_change_log'->>'deleted_at' IS NULL
            ) > 0"));
            !empty($this->date_min) ? $query->addParams([':date_min' => $this->date_min]) : null;
            !empty($this->date_max) ? $query->addParams([':date_max' => $this->date_max]) : null;
        }

        $dataProvider->setPagination(
            CoreModel::setPagination($params, $dataProvider)
        );

        $dataProvider->setSort(
            CoreModel::setSort($params)
        );

        return $dataProvider;
    }
}