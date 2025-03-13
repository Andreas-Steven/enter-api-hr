<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\helpers\Constants;
use app\core\CoreModel;
use app\models\Attendance;
use app\core\CoreMongodb;

class AttendanceSearch extends Attendance
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
    
    public $employee_name;
    public $employee_id;
    public $period;

    public function rules()
    {
        return array_merge(
            [
                [['employee_id'], 'safe'],
                [['employee_name'], 'string'],
                [['id', 'period', 'status'], 'integer'],
                [['date'], 'date', 'format' => 'php:Y-m-d'],
                [['attendance', 'detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
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
        
        $query->andFilterWhere(CoreModel::setChangelogFilters($this));
      
        /* EMPLOYEE NAME OR ID FILTERING */
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
            CoreModel::setPagination($params, $dataProvider)
        );

        $dataProvider->setSort(
            CoreModel::setSort($params)
        );

        return $dataProvider;
    }

    public function mongodbSearch($params) 
    {
        $this->load([CoreMongodb::getModelClassName($this) => $params]);

        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($this, $params)) {
            return $unavailableParams;
        }

        $where = [];
        $orWhere = [];

        CoreMongodb::mdbStatus('status', $this->status, $where);
        CoreMongodb::mdbNumberEqual('id', $this->id, $where);
        CoreMongodb::mdbStringEqual('detail_info.changelog.created_by', $this->created_by, $where);
        CoreMongodb::mdbNumberMultiple('employee_id', $this->employee_id, $orWhere, 'or');

        return Yii::$app->mongodb->search($this, [
            'where' => $where,
            'orWhere' => $orWhere,
        ]);
    }

    public function attendanceReport($params): ActiveDataProvider
    {
        $this->load([CoreMongodb::getModelClassName($this) => $params]);

        $mongodb_param = require(__DIR__ . '/../../config/mongodb.php');
        $client = new \MongoDB\Client($mongodb_param['dsn']);
        $collection = $client->selectCollection($mongodb_param['database'], $this::tableName());

        $sortOrder = $this->sort_dir === 'asc' ? 1 : -1;
        $sortBy = $this->sort_by ?? '_id';
        $page = $this->page ?? 1;
        $pageSize = (int) ($this->page_size ?: Yii::$app->params['pagination']['pageSize']);

        $totalCount = $collection->countDocuments();
        $pageSize = min($totalCount, $pageSize);
        
        $pagination = new \yii\data\Pagination(['totalCount' => $totalCount]);
        $pagination->page = $page - 1;
        $pagination->pageSize = $pageSize;

        $options = [
            'limit' => $pagination->limit,
            'skip' => $pagination->offset,
            'sort' => [$sortBy => $sortOrder],
            'projection' => array_merge(
                ['_id' => false],
                [],
            ),
        ];

        $matchCriteria = [];
        if ($this->period !== null) {
            $matchCriteria['period'] = (int) $this->period;
        }
        if ($this->employee_id !== null) {
            $matchCriteria['employee_id'] = (int) $this->employee_id;
        }
        if ($this->employee_name !== null) {
            $matchCriteria['employee_name'] = $this->employee_name;
        }

        $pipeline = [];
        if (!empty($matchCriteria)) {
            $pipeline[] = ['$match' => $matchCriteria];
        }

        $pipeline = array_merge($pipeline, [
            [
                '$project' => [
                    "employee_id" => 1,
                    "employee_name" => 1,
                    "period" => 1,
                    "in" => [
                        '$cond' => [['$eq' => ['$info.in', true]], 1, 0]
                    ],
                    "out" => [
                        '$cond' => [['$eq' => ['$info.out', true]], 1, 0]
                    ],
                    "late" => [
                        '$cond' => [['$in' => [Constants::ATTENDANCE_LATE, '$info.detail']], 1, 0]
                    ],
                    "leaveEarly" => [
                        '$cond' => [['$in' => [Constants::ATTENDANCE_LEAVE_EARLY, '$info.detail']], 1, 0]
                    ],
                    "missedCheckout" => [
                        '$cond' => [['$in' => [Constants::ATTENDANCE_MISSED_CHECKOUT, '$info.detail']], 1, 0]
                    ],
                    "workHoliday" => [
                        '$cond' => [['$in' => [Constants::ATTENDANCE_WORK_HOLIDAY, '$info.detail']], 1, 0]
                    ]
                ]
            ],
            [
                '$group' => [
                    '_id' => [
                        "employee_id" => '$employee_id',
                        "employee_name" => '$employee_name',
                        "attendance_period" => '$period'
                    ],
                    "total_attendance" => ['$sum' => '$in'],
                    "missed_checkout" => ['$sum' => '$missedCheckout'],
                    "late" => ['$sum' => '$late'],
                    "leave_early" => ['$sum' => '$leaveEarly'],
                    "work_holiday" => ['$sum' => '$workHoliday'],
                ]
            ]
        ]);

        $cursor = $collection->aggregate($pipeline, $options);
        $documents = iterator_to_array($cursor);

        $dataProvider = new ActiveDataProvider([
            'models' => $documents,
            'pagination' => $pagination,
        ]);
        $dataProvider->totalCount = $totalCount;

        return $dataProvider;
    }
}