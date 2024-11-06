<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use app\models\Employee;
use app\helpers\Constants;
use yii\data\ActiveDataProvider;

class EmployeeSearch extends Employee
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
            [['nik', 'name', 'sort', 'sort_by'], 'string'],
            [['id', 'status', 'sso_id', 'division_id', 'page', 'page_size'], 'integer'],
            [['birth_date', 'date_start', 'date_resign'], 'date', 'format' => 'php:Y-m-d'],
            [['contact', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],

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
        $query = Employee::find();
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
                'page' => isset($params['page']) ? $params['page'] : 1,
            ],
        ]);

        
        $this->load(['EmployeeSearch' => $params]);
        
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $dataProvider->pagination->page = isset($params['page']) ? intval($params['page']) - 1 : 0;

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'sso_id' => $this->sso_id,
            'division_id' => $this->division_id,
            'birth_date' => $this->birth_date,
            'date_start' => $this->date_start,
            'date_resign' => $this->date_resign,
        ]);
        
        $query
            ->andFilterWhere(['ilike', 'nik', $this->nik])
            ->andFilterWhere(['ilike', 'name', $this->name])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,created_at}'"), $this->created_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,created_by}'"), $this->created_by])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,updated_at}'"), $this->updated_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,updated_by}'"), $this->updated_by])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,deleted_at}'"), $this->deleted_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,deleted_by}'"), $this->deleted_by]);

            if (!empty($this->contact)) {
                $query->andWhere(new \yii\db\Expression("EXISTS (SELECT 1 FROM jsonb_array_elements_text(contact::jsonb) AS elem WHERE elem ILIKE :contact)"), [':contact' => '%' . $this->contact . '%']);
            }

        return $dataProvider;
    }
}
