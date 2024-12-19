<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\helpers\Constants;
use app\core\CoreModel;
use app\models\Device;

/**
 * DeviceSearch represents the model behind the search form of `app\models\Device`.
 */
class DeviceSearch extends Device
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
                [['id', 'status'], 'integer'],
                [['device_code', 'device_name', 'device_address', 'device_ip'], 'string'],
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

        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($this, $params)) {
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
        $query->andFilterWhere(CoreModel::setLikeFilter($this->device_name, 'device_name'));
        
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'device_code' => $this->device_code,
            'device_address' => $this->device_address,
            'device_ip'  => $this->device_ip,
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
