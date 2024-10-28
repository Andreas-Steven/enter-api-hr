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

    public $quantity;
    public $total_price;

    public function rules()
    {
        return [
            [['id', 'category_id', 'location_id', 'status', 'page', 'page_size'], 'integer'],
            [['detail_info', 'item'], 'safe'],
            [['code', 'sort', 'sort_by'], 'string'],
            [['detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
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
            ) AS item")
        ]);

        if ($this->code != null) {
            $query
            ->select(['id', 'name', 'category_id', 'location_id', 'status', 'detail_info', new \yii\db\Expression("jsonb_build_object(key, value) AS item")])
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

        return $dataProvider;
    }
}