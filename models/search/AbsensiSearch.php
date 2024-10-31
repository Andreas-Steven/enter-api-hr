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
    public $sort;
    public $sort_by;
    public $page;
    public $page_size;

    public function rules()
    {
        return [
            [['id', 'status', 'page_size', 'page'], 'integer'],
            [['attendance', 'detail_info'], 'safe'],
            [['sort', 'sort_by'], 'string'],
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
        $this->sort = isset($params['sort']) ? $params['sort'] : 'id';
        $this->sort_by = isset($params['sort_by']) && $params['sort_by'] == 'asc' ? "SORT_ASC" : "SORT_DESC";
        $this->page_size = isset($params['page_size']) ? $params['page_size'] : 10;
        $this->page = isset($params['page']) ? $params['page'] : 0 ;

        $query = Absensi::find();
        $query->where(Constants::STATUS_NOT_DELETED);

        // add conditions that should always apply here
        $rules = $this->rules();
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($rules, $params)) {
            return $unavailableParams;
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> [
                // 'defaultOrder' => [$this->sort => $this->sort_by],
            ],
            'pagination' => [
                'pageSize' => $this->page_size,
            ],
        ]);

        $this->page = isset($params['page']) ? intval($params['page']) : 1;
        // $dataProvider->pagination->page = $this->page - 1;

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
            $query->andWhere(
                'EXISTS (
                    SELECT 1 
                    FROM jsonb_each(attendance) AS attendance_record 
                    WHERE (attendance_record.value->>\'employee_id\')::int = :employee_id
                )',
                [':employee_id' => $this->employee_id]
            );
        }

        $query
            ->andFilterWhere(['ilike', 'detail_info', $this->detail_info])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,created_at}'"), $this->created_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,created_by}'"), $this->created_by])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,updated_at}'"), $this->updated_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,updated_by}'"), $this->updated_by])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,deleted_at}'"), $this->deleted_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,deleted_by}'"), $this->deleted_by]);

        if (isset($params['sort_by']) && !empty($params['sort_by'])) {
            if ($params['sort_by'] == 'asc') {
                $this->sort_by = 'ASC';
            } else {
                $this->sort_by = 'DESC';
            }
        } else {
            $this->sort_by = 'DESC';
        }

        if (isset($params['sort']) && !empty($params['sort'])) {
            $this->sort = $params['sort'];
        } else {
            $this->sort = 'start_time';
        }

        $attendanceQuery = new \yii\db\Expression(
            "(
                WITH attendance_elements AS (
                    SELECT key, value
                    FROM jsonb_each(attendance)
                ),
                sorted_attendance AS (
                    SELECT key, value, row_number() OVER () AS rn
                    FROM attendance_elements
                    ORDER BY (value->>:sort_field)::time {$this->sort_by}
                )
                SELECT jsonb_object_agg(key, value)
                FROM sorted_attendance
                WHERE rn > :offset AND rn <= :offset + :page_size
            )",
            [
                ':sort_field' => empty($this->sort) ? 'start_time' : $this->sort,
                ':offset' => ($this->page - 1) * $this->page_size,
                ':page_size' => $this->page_size
            ]
        );

        if (isset($this->employee_id)) {
            $attendanceQuery = new \yii\db\Expression(
                "(
                    WITH attendance_elements AS (
                        SELECT key, value
                        FROM jsonb_each(attendance)
                        WHERE (value->>'employee_id')::int = :employee_id
                    ),
                    sorted_attendance AS (
                        SELECT key, value, row_number() OVER () AS rn
                        FROM attendance_elements
                        ORDER BY (value->>:sort_field)::time {$this->sort_by}
                    )
                    SELECT jsonb_object_agg(key, value)
                    FROM sorted_attendance
                    WHERE rn > :offset AND rn <= :offset + :page_size
                )",
                [
                    ':sort_field' => empty($this->sort) ? 'start_time' : $this->sort,
                    ':offset' => ($this->page - 1) * $this->page_size,
                    ':page_size' => $this->page_size,
                    ':employee_id' => $this->employee_id
                ]
            );
        }

        $query->addSelect([
            '*',
            'attendance' => $attendanceQuery,
        ]);
        
        $totalAttendanceCount = (new \yii\db\Query())
            ->select(new \yii\db\Expression("
                COUNT(*)
            "))
            ->from(['a' => 'absensi'])
            ->leftJoin(['ae' => new \yii\db\Expression('jsonb_each(a.attendance)')], 'true')
            ->where(Constants::STATUS_NOT_DELETED)->scalar();
        
        $displayAttendanceCount = (new \yii\db\Query())
            ->select(new \yii\db\Expression("count(*)"))
            ->from(['attendance_elements' => (new \yii\db\Query())
                ->select(new \yii\db\Expression("
                    value, row_number() OVER () AS rn
                "))
                ->from(['a' => 'absensi'])
                ->leftJoin(['ae' => new \yii\db\Expression('jsonb_each(a.attendance)')], 'true')
                ->where(Constants::STATUS_NOT_DELETED)
                ->orderBy(new \yii\db\Expression("ae.value->>'start_time' DESC"))
            ])
            ->where(new \yii\db\Expression("rn > :offset AND rn <= :limit", [
                ':offset' => ($this->page - 1) * $this->page_size,
                ':limit' => $this->page * $this->page_size
            ]))->scalar();

        return [
            'dataProvider' => $dataProvider,
            'totalAttendanceCount' => $totalAttendanceCount,
            'displayAttendanceCount' => $displayAttendanceCount,
        ];
    }
}