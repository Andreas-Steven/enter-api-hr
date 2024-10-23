<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use app\models\Absensi;
use app\helpers\Constants;
use yii\data\ActiveDataProvider;

/**
 * AbsensiSearch represents the model behind the search form of `app\models\Absensi`.
 */
class AbsensiSearch extends Absensi
{
    public $employee_id;
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
            [['attendance', 'blacklist', 'detail_info'], 'safe'],
            [['dayoff'], 'boolean'],
            [['date'], 'date', 'format' => 'Y-m-d'],
            [['employee_id'], 'integer'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Absensi::find();

            // ->select([
            //     'absensi.id',
            //     'absensi.date',
            //     'absensi.dayoff',
            //     new \yii\db\Expression("attendance_record ->> 'employee_id' AS employee_id"),
            //     new \yii\db\Expression("attendance_record ->> 'employee_name' AS employee_name"),
            //     new \yii\db\Expression("attendance_record ->> 'start_time' AS start_time"),
            //     new \yii\db\Expression("attendance_record -> 'department' ->> 'name' AS department_name"),
            //     'absensi.status',
            //     'absensi.detail_info',
            // ])
            // ->crossJoin(['attendance_record' => new \yii\db\Expression('jsonb_array_elements(attendance)')]);

        // add conditions that should always apply here
        $rules = $this->rules();
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($rules, $params)) {
            return $unavailableParams;
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> [
                'defaultOrder' => ['id' => SORT_ASC]
            ],
            'pagination' => ['pageSize' => 2]
        ]);

        $this->load(['AbsensiSearch' => $params]);

        
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'date' => $this->date,
        ]);

        if (isset($this->employee_id)) {
            $query->andFilterWhere([
                'date' => $this->date,
            ]);

            $query->andFilterWhere(new \yii\db\Expression(
                "EXISTS (SELECT 1 FROM jsonb_array_elements(attendance) AS attendance_record WHERE attendance_record ->> 'employee_id' = :employee_id)", [':employee_id' => $this->employee_id]
            ));
        }

        $query
            // ->andFilterWhere(['ilike', 'date', $this->date])
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
