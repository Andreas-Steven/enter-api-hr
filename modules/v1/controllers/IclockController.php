<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use app\helpers\Constants;
use yii\filters\VerbFilter;
use app\core\CoreController;

use app\models\Device;
use app\models\Employee;
use app\models\Attendance;

class IclockController extends CoreController
{
	public function behaviors()
    {
		$behaviors = parent::behaviors();

		$behaviors['verbs']['actions'] = array_merge(
			$behaviors['verbs']['actions'],
			[
				'cdata' => ['GET', 'POST'],
			]
		);

        return $behaviors;
    }

    public function actionCdata()
    {
        $request = Yii::$app->getRequest();

        if ($request->isGet) {
            return $this->actionCreate();
        } 
        
        if ($request->isPost) {
            return $this->actionAttendance();
        }
    }

    public function actionCreate()
	{
		$model = new Device();
		$request = Yii::$app->getRequest();
		$scenario = Constants::SCENARIO_ADMS;

        $params['name'] = Constants::ZKTeco_DEFAULT_NAME;
        $params['code'] = $request->get('SN');
        $params['url'] = $request->getQueryParams();

        CoreController::unavailableParams($model, $params);
		
		$params['status'] = Constants::STATUS_DRAFT;
		$model->scenario = 	$scenario;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}

    protected function actionAttendance()
    {
        $request = Yii::$app->getRequest();
        $params = $request->getQueryParams();

        # LOG USING YII LOGGER
        // Log the incoming request
        // Yii::$app->db->createCommand()->insert('finger_log', [
        //     'url' => $jsonData,
        //     'data' => $jsonData,
        // ])->execute();

        try {
            $errors = [];
            $attendances = [];
            $content = preg_split('/\r\n|\r|\n/', $request->getRawBody());
          
            foreach ($content as $record) {
                if (empty($record)) continue;

                $data = explode("\t", $record);
                if (!empty($data) && isset($data[0]) && is_numeric($data[0])) {
                    $employeeId = $data[0];
                    $timestamp = $data[1] ?? ' ';
                    $status1 = $this->validateInteger($data[2]) ?? -1;
                    $sn = $request->get('SN') ?? ' ';

                    return $attendances[] = [
                        'sn' => $sn,
                        'table' => $request->get('table', ' '),
                        'stamp' => $request->get('Stamp', ' '),
                        'employee_id' => intval($employeeId),
                        'timestamp' => $timestamp,
                        'status1' => $status1,
                        'status2' => $this->validateInteger($data[3]) ?? -1,
                        'status3' => $this->validateInteger($data[4]) ?? -1,
                        'status4' => $this->validateInteger($data[5]) ?? -1,
                        'status5' => $this->validateInteger($data[6]) ?? -1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                } else {
                    $errors[] = 'Invalid or incomplete data: ' . $record;
                }
            }
        } catch (\Exception $e) {
            return $errorData = [
                'data' => $e->getMessage() . '::Line::' . $e->getLine(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
        }
    }

    private function validateInteger($value)
    {
        return isset($value) && $value !== '' ? intval($value) : null;
    }
}
