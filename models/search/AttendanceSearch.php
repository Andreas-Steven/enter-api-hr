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
        $sortBy = '_id.'.($this->sort_by ?? 'employee_id');
        $page = max(1, (int) ($this->page ?? 1));
        $pageSize = (int) ($this->page_size ?: Yii::$app->params['pagination']['pageSize']);

        $matchCriteria = [];
        if (!empty($this->period)) {
            $matchCriteria['period'] = (int) $this->period;
        }
        
        if (!empty($this->employee_id)) {
            $matchCriteria['employee_id'] = (int) $this->employee_id;
        }
        
        if (!empty($this->employee_name)) {
            $matchCriteria['employee_name'] = $this->employee_name;
        }
        
        $workingDaysInfo = $this->getWorkingDays();
        
        $pipeline = [];
        if (!empty($matchCriteria)) {
            $pipeline[] = ['$match' => $matchCriteria];
        }

        $pipeline[] = ['$group' => [
            '_id' => [
                'employee_id' => '$employee_id',
                'employee_name' => '$employee_name',
                'period' => '$period',
            ],
            'total_in' => ['$sum' => ['$cond' => [['$eq' => ['$info.in', true]], 1, 0]]],
            'total_out' => ['$sum' => ['$cond' => [['$eq' => ['$info.out', true]], 1, 0]]],
            'total_late' => ['$sum' => ['$cond' => [['$in' => [Constants::ATTENDANCE_LATE, '$info.detail']], 1, 0]]],
            'total_leave_early' => ['$sum' => ['$cond' => [['$in' => [Constants::ATTENDANCE_LEAVE_EARLY, '$info.detail']], 1, 0]]],
            'total_missed_checkout' => ['$sum' => ['$cond' => [['$in' => [Constants::ATTENDANCE_MISSED_CHECKOUT, '$info.detail']], 1, 0]]],
            'total_work_holiday' => ['$sum' => ['$cond' => [['$in' => [Constants::ATTENDANCE_WORK_HOLIDAY, '$info.detail']], 1, 0]]],
        ]];
    
        $pipeline[] = ['$addFields' => [
            'working_days' => [
                'workingDays' => $workingDaysInfo['workingDays'],
                'saturdaysCount' => $workingDaysInfo['saturdaysCount'],
                'sundaysCount' => $workingDaysInfo['sundaysCount'],
                'startDate' => $workingDaysInfo['startDate'],
                'endDate' => $workingDaysInfo['endDate'],
            ],
            'report' => [
                'total_in' => '$total_in',
                'total_out' => '$total_out',
                'total_late' => '$total_late',
                'total_leave_early' => '$total_leave_early',
                'total_missed_checkout' => '$total_missed_checkout',
                'total_work_holiday' => '$total_work_holiday',
            ]
        ]];
    
        $pipeline[] = ['$project' => [
            '_id' => 1,
            'working_days' => 1,
            'report' => 1,
        ]];
    
        $pipeline[] = ['$facet' => [
            'total_count' => [['$count' => 'total']],
            'data' => [
                ['$sort' => [$sortBy => $sortOrder]],
                ['$skip' => ($page - 1) * $pageSize],
                ['$limit' => $pageSize],
            ],
        ]];

        $resultsArray = iterator_to_array($collection->aggregate($pipeline));
        $totalCount = $resultsArray[0]['total_count'][0]['total'] ?? 0;
        $documents = iterator_to_array($resultsArray[0]['data']) ?? [];

        $finalOutput[] = [
            "period" => (int) $this->period ?? 0,
            "working_day" => $workingDaysInfo['workingDays'],
            "saturday_count" => $workingDaysInfo['saturdaysCount'],
            "sunday_count" => $workingDaysInfo['sundaysCount'],
            "start_date" => $workingDaysInfo['startDate'],
            "end_date" => $workingDaysInfo['endDate'],
            "report" => array_map(function ($doc) {
                return [
                    "employee_id" => $doc['_id']['employee_id'],
                    "employee_name" => $doc['_id']['employee_name'],
                    "attendance" => [
                        "total_in" => $doc['report']['total_in'],
                        'total_out' => $doc['report']['total_out'],
                        'total_late' => $doc['report']['total_late'],
                        'total_leave_early' => $doc['report']['total_leave_early'],
                        'total_missed_checkout' => $doc['report']['total_missed_checkout'],
                        'total_work_holiday' => $doc['report']['total_work_holiday'],
                    ],
                ];
            }, $documents)
        ];

        return new ActiveDataProvider([
            'models' => $finalOutput,
            'pagination' => new \yii\data\Pagination([
                'totalCount' => $totalCount,
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ]),
            'totalCount' => $totalCount,
        ]);
    }

    public function getWorkingDays()
    {
        $currentDate = new \DateTime();
        $currentYear = $currentDate->format('Y');
        $currentMonth = $currentDate->format('m');

        $startDate = new \DateTime("$currentYear-$currentMonth-26");
        $startDate->modify('-1 month');
        $formattedStartDate = $startDate->format('Y-m-d');

        $endDate = new \DateTime("$currentYear-$currentMonth-25");
        $formattedEndDate = $endDate->format('Y-m-d');

        $workingDays = 0;
        $saturdaysCount = 0;
        $sundaysCount = 0;

        while ($startDate <= $endDate) {
            $dayOfWeek = $startDate->format('N');

            if ($dayOfWeek >= 1 && $dayOfWeek <= 5) {
                $workingDays++;
            } else {
                if ($dayOfWeek == 6) {
                    $saturdaysCount++;
                } elseif ($dayOfWeek == 7) {
                    $sundaysCount++;
                }
            }

            $startDate->modify('+1 day');
        }

        return [
            'workingDays' => $workingDays,
            'saturdaysCount' => $saturdaysCount,
            'sundaysCount' => $sundaysCount,
            'startDate' => $formattedStartDate,
            'endDate' => $formattedEndDate,
        ];
    }
}