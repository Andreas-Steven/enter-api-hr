<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use app\models\Attendance;
use app\helpers\Constants;
use yii\data\ActiveDataProvider;

class AttendanceSearch extends attendance
{
    public $created_at;
    public $created_by;
    public $updated_at;
    public $updated_by;
    public $deleted_at;
    public $deleted_by;
    public $employee_name;
    public $sort;
    public $sort_by;
    public $page;
    public $page_size;

    public function rules()
    {
        return [
            [['id', 'status', 'page_size', 'page'], 'integer'],
            [['attendance', 'detail_info'], 'safe'],
            [['sort', 'sort_by', 'employee_name'], 'string'],
            [['date'], 'date', 'format' => 'Y-m-d'],
        ];
    }

    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    public function search($params)
    {
        $this->sort_by = isset($params['sort_by']) && $params['sort_by'] == 'asc' ? "ASC" : "DESC";
        $this->page_size = isset($params['page_size']) ? $params['page_size'] : 10;
        $this->page = isset($params['page']) ? $params['page'] - 1 : 0;

        $query = Attendance::find();
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
                // 'pageSize' => $this->page_size,
                'page' => $this->page
            ],
        ]);

        $this->load(['AttendanceSearch' => $params]);
        
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

        $query
            ->andFilterWhere(['ilike', 'detail_info', $this->detail_info])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,created_at}'"), $this->created_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,created_by}'"), $this->created_by])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,updated_at}'"), $this->updated_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,updated_by}'"), $this->updated_by])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,deleted_at}'"), $this->deleted_at])
            ->andFilterWhere(['ilike', new \yii\db\Expression("detail_info #>> '{change_log,deleted_by}'"), $this->deleted_by]);

        $attendanceQuerySql = "(
            WITH attendance_elements AS (
                SELECT value
                FROM jsonb_each(attendance)
                " . (isset($this->employee_name) ? "WHERE value->>'employee_name' ILIKE :employee_name" : "") . "
                " . (isset($this->division) ? "AND value->'division'->>'name' ILIKE :division" : "") . "
            ),
            sorted_attendance AS (
                SELECT value, row_number() OVER (ORDER BY value->>:sort_field {$this->sort_by}) AS rn
                FROM attendance_elements
            ),
            paginated_attendance AS (
                SELECT value
                FROM sorted_attendance
                WHERE rn > :offset AND rn <= :offset + :page_size
            )
            SELECT jsonb_agg (value)
            FROM paginated_attendance
        )";

        $attendanceQueryParams = [
            ':sort_field' => isset($this->sort) ? $this->sort : 'start_time',
            ':offset' => ($this->page - 1) * $this->page_size,
            ':page_size' => $this->page_size
        ];
        
        if (isset($this->employee_name)) {
            $attendanceQueryParams[':employee_name'] = '%' . $this->employee_name . '%';
        }

        if (isset($this->division)) {
            $attendanceQueryParams[':division'] = '%' . $this->division . '%';
        }

        $attendanceQuery = new \yii\db\Expression($attendanceQuerySql, $attendanceQueryParams);

        $query->addSelect([
            '*',
            'absensi' => $attendanceQuery,
        ]);
        
        $totalAttendanceCount = (new \yii\db\Query())
            ->from('attendance')
            ->leftJoin(['ae' => new \yii\db\Expression('jsonb_each(attendance.attendance)')], 'true')
            ->where(Constants::STATUS_NOT_DELETED)
            ->count();

        $displayAttendanceCount = (new \yii\db\Query())
            ->from(['attendance' => (new \yii\db\Query())
                ->select(['value', new \yii\db\Expression('row_number() OVER () AS rn')])
                ->from('attendance')
                ->leftJoin(['ae' => new \yii\db\Expression('jsonb_each(attendance.attendance)')], 'true')
            ])
            ->where(['between', 'rn', ($this->page - 1) * $this->page_size, $this->page_size])
            ->count();

        return [
            'dataProvider' => $dataProvider,
            'totalAttendanceCount' => $totalAttendanceCount,
            'displayAttendanceCount' => $displayAttendanceCount,
        ];
    }
}