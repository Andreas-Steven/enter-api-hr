<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use app\models\Device;
use app\helpers\Constants;
use yii\data\ActiveDataProvider;

/**
 * DeviceSearch represents the model behind the search form of `app\models\Device`.
 */
class DeviceSearch extends Device
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
            [['device_code', 'device_name', 'device_address', 'device_ip'], 'string', 'max' => 255],
            [['detail_info', 'device_location'], 'safe'],

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

        $query = Device::find();
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
            'status' => $this->status,
            'device_code' => $this->device_code,
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
