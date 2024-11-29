<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\db\Expression;
use app\helpers\Constants;
use yii\data\ActiveDataProvider;

use app\models\Payroll;

/**
 * PayrollSearch represents the model behind the search form of `app\models\Payroll`.
 */
class PayrollSearch extends Payroll
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
        return [
            [['id', 'status'], 'integer'],
            [['pay_period'], 'string'],
            [['salary_detail', 'detail_info'], 'safe'],
            [['pay_date'], 'date', 'format' => 'php:Y-m-d'],

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

        $query = Payroll::find();
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
        $query->andFilterWhere([
            'id' => $this->id,
            'pay_period' => $this->pay_period,
            'pay_date' => $this->pay_date,
            'status' => $this->status,
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
