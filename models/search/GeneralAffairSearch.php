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

    // Filtering variables
    public $name;
    public $code;
    public $price;
    public $buying_date;

    public function rules()
    {
        return array_merge(
            [
                [['code', 'name'], 'string'],
                [['id', 'product_id', 'category_id', 'location_id', 'status'], 'integer'],
                [['asset', 'buying_date', 'price', 'detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
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
            'product_id' => $this->product_id,
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

        // Date range filtering
        $dateConditions = [];
        if (isset($this->buying_date) && !empty($this->buying_date)) {
            if (strpos($this->buying_date, ',') !== false) {
                list($startDate, $endDate) = array_map('trim', explode(',', $this->buying_date));
                !empty($startDate) ? $dateConditions[] = "(value->>'buying_date')::date >= :startDate" : null;
                !empty($endDate) ? $dateConditions[] = "(value->>'buying_date')::date <= :endDate" : null;
                !empty($startDate) ? $query->addParams([':startDate' => $startDate]) : null;
                !empty($endDate) ? $query->addParams([':endDate' => $endDate]) : null;
            } else {
                $dateConditions[] = "(value->>'buying_date')::date = :buying_date";
                $query->addParams([':buying_date' => $this->buying_date]);
            }
        }

        // Price range filtering
        $priceConditions = [];
        if (isset($this->price) && !empty($this->price)) {
            if (strpos($this->price, ',') !== false) {
                list($priceMin, $priceMax) = array_map('trim', explode(',', $this->price));
                !empty($priceMin) ? $priceConditions[] = "(value->>'price')::numeric >= :priceMin" : null;
                !empty($priceMax) ? $priceConditions[] = "(value->>'price')::numeric <= :priceMax" : null;
                !empty($priceMin) ? $query->addParams([':priceMin' => $priceMin]) : null;
                !empty($priceMax) ? $query->addParams([':priceMax' => $priceMax]) : null;
            } else {
                $priceConditions[] = "(value->>'price')::numeric = :price";
                $query->addParams([':price' => $this->price]);
            }
        }

        $conditions = array_merge($dateConditions, $priceConditions);

        if ($conditions) {
            $query->andWhere(new \yii\db\Expression("(
                SELECT COUNT(*)
                FROM jsonb_each(general_affair.asset->'items') AS items_record(key, value)
                WHERE " . implode(' AND ', $conditions) . "
                AND value->'items_change_log'->>'deleted_at' IS NULL
            ) > 0"));
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