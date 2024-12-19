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

        $query = Employee::find();
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
            'sso_id' => $this->sso_id,
            'division_id' => $this->division_id,
            'birth_date' => $this->birth_date,
            'date_start' => $this->date_start,
            'date_resign' => $this->date_resign,
        ]);
        
        $query->andFilterWhere(Yii::$app->coreAPI->changelogDateFilters($this));
        $query->andFilterWhere(Yii::$app->coreAPI->changelogFilters($this));
        
        $dataProvider->setPagination(
            Yii::$app->coreAPI->setPagination($params, $dataProvider)
        );

        $dataProvider->setSort(
            Yii::$app->coreAPI->setSort($params)
        );

        return $dataProvider;
    }
}
