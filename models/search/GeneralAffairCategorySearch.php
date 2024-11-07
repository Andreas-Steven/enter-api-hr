<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use app\helpers\Constants;
use yii\data\ActiveDataProvider;
use app\models\GeneralAffairCategory;

/**
 * GeneralAffairCategorySearch represents the model behind the search form of `app\models\GeneralAffairCategory`.
 */
class GeneralAffairCategorySearch extends GeneralAffairCategory
{
    public $created_at;
    public $created_by;
    public $updated_at;
    public $updated_by;
    public $deleted_at;
    public $deleted_by;
    public $sort;
    public $sort_by;
    public $page;
    public $page_size;

    public function rules()
    {
        return [
            [['id', 'status'], 'integer'],
            [['name'], 'safe'],
            [['detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
            
            // Pagination and Sorting Section
            [['sort', 'sort_by'], 'string'],
            [['page', 'page_size'], 'integer'],
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
        $this->page = isset($params['page']) ? $params['page'] : 0 ;

        $query = GeneralAffairCategory::find();
        $query->where(Constants::STATUS_NOT_DELETED);

        // add conditions that should always apply here
        $rules = $this->rules();
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($rules, $params)) {
            return $unavailableParams;
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort' => [
                'defaultOrder' => [
                    isset($params['sort']) ? $params['sort'] : 'id' => isset($params['sort_by']) && $params['sort_by'] == 'asc' ? SORT_ASC : SORT_DESC
                ],
            ],
            'pagination' => [
                'pageSize' => isset($params['page_size']) ? $params['page_size'] : 10,
                'page' => isset($params['page']) ? $params['page'] : 0,
            ],
        ]);

        $this->load(['GeneralAffairCategorySearch' => $params]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['ilike', 'name', $this->name])
            // ->andFilterWhere(['ilike', 'detail_info', $this->detail_info])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,created_at}'"), $this->created_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,created_by}'"), $this->created_by])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,updated_at}'"), $this->updated_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,updated_by}'"), $this->updated_by])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,deleted_at}'"), $this->deleted_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,deleted_by}'"), $this->deleted_by]);

        return $dataProvider;
    }
}
