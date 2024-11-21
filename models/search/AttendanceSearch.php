<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use app\models\Attendance;
use app\helpers\Constants;
use yii\data\ActiveDataProvider;

class AttendanceSearch extends Attendance
{
    public $created_at;
    public $created_by;
    public $updated_at;
    public $updated_by;
    public $deleted_at;
    public $deleted_by;
    public $employee_name;
    public $employee_id;
    public $sort;
    public $sort_by;
    public $page;
    public $page_size;

    public function rules()
    {
        return [
            [['employee_name'], 'string'],
            [['attendance', 'detail_info'], 'safe'],
            [['date'], 'date', 'format' => 'Y-m-d'],
            [['id', 'status','employee_id'], 'integer'],
            
            [['sort', 'sort_by'], 'string'],
            [['page', 'page_size'], 'integer'],
            [['detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
            [['page'], function ($attribute, $params, $validator) {
                Yii::$app->coreAPI->validatePage($attribute, $params, $this);
            }],
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

        $query = Attendance::find();
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
            'date' => $this->date,
        ]);

        $query->andFilterWhere(Yii::$app->coreAPI->changelogDateFilters($this));
        $query->andFilterWhere(Yii::$app->coreAPI->changelogFilters($this));

        // $query->addSelect([
        //     '*',
        //     'attendance' => new \yii\db\Expression("(
        //         WITH sorted_attendance AS (
        //             SELECT key, value
        //             FROM attendance,
        //                 jsonb_each(attendance.attendance)
        //             ORDER BY (value->>'start_time')::text DESC
        //         )
        //         SELECT jsonb_object_agg(key, value) AS attendance
        //         FROM sorted_attendance
        //     )")
        // ]);
        
        if ($this->employee_name || $this->employee_id) {
            $query->addSelect([
                '*',
                'attendance' => new \yii\db\Expression( "(
                    WITH attendance_elements AS (
                        SELECT key, value
                        FROM jsonb_each(attendance)
                        WHERE (value->>'employee_id')::int = :employee_id 
                        OR (value->>'employee_name')::text ILIKE :employee_name
                    )
                    SELECT jsonb_object_agg(key, value)
                    FROM attendance_elements)",
                    [
                        ':employee_id' => $this->employee_id,
                        ':employee_name' => $this->employee_name,
                    ]
                )
            ]);
        }

        $dataProvider->setPagination(
            Yii::$app->coreAPI->setPagination($params, $dataProvider)
        );

        $dataProvider->setSort(
            Yii::$app->coreAPI->setSort($params)
        );

        return $dataProvider;
    }
}