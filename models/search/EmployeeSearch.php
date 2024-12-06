<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\helpers\Constants;
use app\models\CoreModel;
use app\models\Employee;

class EmployeeSearch extends Employee
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

    public function rules()
    {
        return array_merge(
            [
                [['nik', 'name'], 'string'],
                [['id', 'status', 'sso_id', 'division_id'], 'integer'],
                [['birth_date', 'date_start', 'date_resign'], 'date', 'format' => 'php:Y-m-d'],
                [['detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
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
        $query->andFilterWhere(CoreModel::setLikeFilter($this->name, 'name'));
        
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'sso_id' => $this->sso_id,
            'division_id' => $this->division_id,
            'birth_date' => $this->birth_date,
            'date_start' => $this->date_start,
            'date_resign' => $this->date_resign,
        ]);
        
        $query->andFilterWhere(CoreModel::setChangelogFilters($this));
        
        $dataProvider->setPagination(
            CoreModel::setPagination($params, $dataProvider)
        );

        $dataProvider->setSort(
            CoreModel::setSort($params)
        );

        return $dataProvider;
    }
}
