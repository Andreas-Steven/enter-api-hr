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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'category_id', 'location_id', 'status'], 'integer'],
            [['detail_info', 'item'], 'safe'],
            [['detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        // SELECT jsonb_object_agg(elemen.key, elemen.value) AS filtered_items
        // FROM general_affair
        // CROSS JOIN LATERAL jsonb_each(item->'items') AS elemen
        // WHERE elemen.value->>'status' <> '4'; 

        $query = GeneralAffair::find();
        if (isset($params['code']) != null) {
            $query
                ->select('elemen.value AS item')
                ->from('general_affair')
                ->join('CROSS JOIN LATERAL', 'jsonb_each(item->\'items\') AS elemen')
                ->where('elemen.value->>\'code\' = :code', [':code' => $params['code']])
                ->andWhere('elemen.value->>\'status\' != :status', [':status' => '4'])
                ->all();
        } elseif (isset($params['id']) != null) {
            $query->where(['id' => $params['id']]);
            $query->andWhere(Constants::STATUS_NOT_DELETED);
        }

        // add conditions that should always apply here
        // $rules = $this->rules();
        // if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($rules, $params)) {
        //     return $unavailableParams;
        // }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> [
                'defaultOrder' => ['id' => SORT_ASC]
            ],
            // 'pagination' => ['pageSize' => 2]
        ]);

        $this->load(['GeneralAffairSearch' => $params]);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        dd($this->id);
        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
        ]);

        $query
            ->andFilterWhere(['ilike', 'name', $this->name])
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
