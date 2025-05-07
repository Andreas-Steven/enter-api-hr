<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\helpers\Constants;
use app\core\CoreModel;
use app\models\AttendancePermission;

/**
 * AttendancePermissionSearch represents the model behind the search form of `app\models\AttendancePermission`.
 */
class AttendancePermissionSearch extends AttendancePermission
{
    public $page;
    public $page_size;
    public $sort_dir;
    public $sort_by;
    public $created_at;
    public $created_by;
    public $updated_at;
    public $updated_by;
    public $deleted_at;
    public $deleted_by;
    public $approved_at;
    public $approved_by;
    public $employee_name;

    public function rules()
    {
        return array_merge(
            [
                [['id', 'sync_mdb'], 'integer'],
                [['employee_id', 'permission_type', 'approved', 'employee_name', 'date', 'note', 'approved_note', 'revision', 'status', 'detail_info'], 'safe'],
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

        $query = AttendancePermission::find();
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
        ]);

        if ($this->employee_name) {
            $conditions = ['and'];
            $conditions[] = ['=', new \yii\db\Expression("(detail_info #>> '{employee,name}')"), $this->employee_name];
            $query->andFilterWhere($conditions);
        }

        if ($this->date) {
            $dateValue = $this->date;
            $conditions = ['and'];

            if (strpos($dateValue, ',') !== false) {
                list($startDate, $endDate) = array_map('trim', explode(',', $dateValue));

                $conditions[] = [
                    'and',
                    ['>=', "date", $startDate],
                    ['<=', "date", $endDate],
                ];
            } else {
                $conditions[] = ['=', "date", $dateValue];
            }
            
            $query->andFilterWhere($conditions);
        }

        $query->andFilterWhere(['employee_id' => $this->employee_id ? explode(',', $this->employee_id) : $this->employee_id]);
        $query->andFilterWhere(['permission_type' => $this->permission_type ? explode(',', $this->permission_type) : $this->permission_type]);
        $query->andFilterWhere(['approved' => $this->approved ? explode(',', $this->approved) : $this->approved]);
        $query->andFilterWhere(['status' => $this->status ? explode(',', $this->status) : $this->status]);

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
