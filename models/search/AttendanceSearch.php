<?php

namespace app\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\helpers\Constants;
use app\core\CoreModel;
use app\core\CoreMongodb;
use app\exceptions\ErrorMessage;
use app\models\Attendance;
use app\models\AttendanceHoliday;

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
    
    public $period;
    public $dayoff;
    public $employee_id;
    public $employee_name;

    public function rules()
    {
        return array_merge(
            [
                [['dayoff'], 'boolean'],
                [['id', 'period', 'status'], 'integer'],
                [['date', 'employee_name', 'employee_id', 'attendance', 'detail_info', 'created_at', 'created_by', 'updated_at', 'updated_by', 'deleted_at', 'deleted_by'], 'safe'],
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
        ]);
        
        $query->andFilterWhere(CoreModel::setChangelogFilters($this));

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
        
        if (isset($this->dayoff)) {
            $conditions = ['and'];
            $conditions[] = ['=', new \yii\db\Expression("CAST(detail_info->>'dayoff' AS BOOLEAN)"), (bool) $this->dayoff];
            $query->andFilterWhere($conditions);
        }

        $query->andFilterWhere(['period' => $this->period ? explode(',', $this->period) : $this->period]);

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

        $matchCriteria = array_filter([
            'period' => !empty($this->period) ? (int) $this->period : null,
            'employee_id' => !empty($this->employee_id) ? (int) $this->employee_id : null,
            'employee_name' => $this->employee_name ?? null,
        ]);


        if (!empty($this->period)) {
            $inCondition = fn($field, $value) => ['$sum' => ['$cond' => [['$in' => [$value, '$'."info.$field"]], 1, 0]]];
            $eqCondition = fn($field, $value) => ['$sum' => ['$cond' => [['$eq' => ['$info.'.$field, $value]], 1, 0]]];

            $pipeline = [
                ['$match' => $matchCriteria],
                ['$group' => [
                    '_id' => [
                        'employee_id' => '$employee_id',
                        'employee_name' => '$employee_name',
                        'period' => '$period',
                    ],
                    'division_name' => ['$first' => '$division.name'],
                    'total_in' => $eqCondition('in', true),
                    'total_out' => $eqCondition('out', true),
                    'total_late' => $inCondition('detail', Constants::ATTENDANCE_LATE),
                    'total_leave_early' => $inCondition('detail', Constants::ATTENDANCE_LEAVE_EARLY),
                    'total_missed_checkout' => $inCondition('detail', Constants::ATTENDANCE_MISSED_CHECKOUT),
                    'total_work_holiday' => $inCondition('detail', Constants::ATTENDANCE_WORK_HOLIDAY),
                    'total_blacklist' => ['$sum' => ['$size' => ['$ifNull' => ['$detail_info.blacklist', []]]]],
                ]],
                ['$facet' => [
                    'total_count' => [['$count' => 'total']],
                    'data' => [
                        ['$sort' => [$sortBy => $sortOrder]],
                        ['$skip' => ($page - 1) * $pageSize],
                        ['$limit' => $pageSize],
                    ],
                ]]
            ];

            $resultsArray = iterator_to_array($collection->aggregate($pipeline));
            $totalCount = $resultsArray[0]['total_count'][0]['total'] ?? 0;
            $documents = iterator_to_array($resultsArray[0]['data']) ?? [];
            $workingDaysInfo = $this->getWorkingDays($this->period);
        }

        $finalOutput = empty($documents) ? [] : [
            [
                "period" => (int) $this->period,
                "working_day" => $workingDaysInfo['workingDays'],
                "saturday_count" => $workingDaysInfo['saturdaysCount'],                                
                "sunday_count" => $workingDaysInfo['sundaysCount'],
                "dayoff_count" => $workingDaysInfo['dayoffCount'],
                "start_date" => $workingDaysInfo['startDate'],
                "end_date" => $workingDaysInfo['endDate'],
                "report" => array_map(function ($doc) {
                    return [
                        "employee_id" => $doc['_id']['employee_id'],
                        "employee_name" => $doc['_id']['employee_name'],
                        "division_name" => $doc['division_name'],
                        "attendance_recap" => [
                            "total_in" => $doc['total_in'],
                            'total_out' => $doc['total_out'],
                            'total_late' => $doc['total_late'],
                            'total_leave_early' => $doc['total_leave_early'],
                            'total_missed_checkout' => $doc['total_missed_checkout'],
                            'total_work_holiday' => $doc['total_work_holiday'],
                            'total_blacklist' => $doc['total_blacklist'],
                        ],
                    ];
                }, $documents),
            ]
        ];

        return new ActiveDataProvider([
            'models' => $finalOutput ?? [],
            'pagination' => new \yii\data\Pagination([
                'totalCount' => $totalCount ?? 0,
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ]),
            'totalCount' => $totalCount ?? 0,
        ]);
    }

    public function getWorkingDays($period)
    {
        $year = substr($period, 0, 4);
        $month = substr($period, 4, 2);
   
        $startDate = (new \DateTime("$year-$month-26"))->modify('-1 month'); 
        $endDate = (new \DateTime("$year-$month-25")); 
    
        $isWeekend = function ($date) {
            $dayOfWeek = $date->format('N');
            return $dayOfWeek == 6 || $dayOfWeek == 7;
        };
    
        $totalDays = $saturdaysCount = $sundaysCount = 0;
    
        for ($date = clone $startDate; $date <= $endDate; $date->modify('+1 day')) {
            $totalDays++;
            if ($date->format('N') == 6) {
                $saturdaysCount++;
            } elseif ($date->format('N') == 7) {
                $sundaysCount++;
            }
        }
    
        $workingDays = $totalDays - ($saturdaysCount + $sundaysCount);

        $dayoffCount = AttendanceHoliday::find()
            ->where([
                'period' => $period, 
                'status' => Constants::STATUS_ACTIVE,
            ])->count();
    
        return [
            'workingDays' => ($workingDays - $dayoffCount),
            'saturdaysCount' => $saturdaysCount,
            'sundaysCount' => $sundaysCount,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'dayoffCount' => $dayoffCount,

        ];
    }
}