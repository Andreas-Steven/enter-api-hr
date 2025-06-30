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
        $model = new Attendance();
        $scenario = Constants::SCENARIO_CREATE;

        $systemDate = date('Y-m-d');
        $request = Yii::$app->getRequest();
        $params = $request->getQueryParams();
        $serialNumber = $request->getQueryParam('SN');
        $content = preg_split('/\r\n|\r|\n/', $request->getRawBody());

        foreach ($content as $record) {
            if (empty($record)) {
                continue;
            }

            $data = explode("\t", $record);

            if (empty($data) || !isset($data[0]) || !is_numeric($data[0])) {
                return 'Invalid or incomplete data: ' . implode("\t", $data);
            }
            
            $employeeId = intval($data[0]);
            $date = date('Y-m-d', strtotime($data[1])) ?? null;
            $timestamp = date('H:i:s', strtotime($data[1])) ?? null;
            $machineStatus = $this->validateInteger($data[2]) ?? -1;
            $params = $this->buildAttendanceParams($serialNumber, $params, $data, $employeeId, $machineStatus, $systemDate); 
            $this->buildAttendanceLog($serialNumber, $params, $employeeId, $timestamp, $machineStatus, $data);
        }

        $existingModel = $model::findOne([
            'date' => $systemDate,
            'status' => Constants::STATUS_ACTIVE,
        ]);

        if (empty($existingModel)) {
            $updateLastAttendance = $model::findAll([
                'status' => Constants::STATUS_ACTIVE,
            ]);

            if (!empty($updateLastAttendance)) {
                foreach ($updateLastAttendance as $attendanceModel) {
                    $attendanceModel->scenario = Constants::SCENARIO_UPDATE;
                    $attendanceModel->save();
                }

                Yii::$app->db->createCommand()
                    ->update(
                        $model::tableName(),
                        ['status' => Constants::STATUS_COMPLETED],
                        ['and', ['status' => Constants::STATUS_ACTIVE], ['<>', 'date', $systemDate]]
                    )->execute();
            }
        }

        if ($existingModel !== null) {
            $model = $existingModel;
        }
        
        $params['date'] = $date;
        $params['system_date'] = $systemDate;
        $params['status'] = Constants::STATUS_ACTIVE;
        $model->scenario = $scenario;

        if ($model->load($params, '') && $model->validate()) {
            if ($model->save()) {	
                # Make Attendance Logs
                $this->updateMongoDB($model);
                // 

                return CoreController::coreSuccess($model);
            }
        }

        return CoreController::coreError($model);
}

    private function buildAttendanceParams($serialNumber, $params, $data, $employeeId, $machineStatus)
    {
        $timestamp = date('H:i:s', strtotime($data[1])) ?? null;
        
        $params['attendance'][$employeeId]['employee_id'] = $employeeId;
        $params['attendance'][$employeeId]['start_time'] = $timestamp;
        $params['attendance'][$employeeId]['end_time'] = $timestamp;
        $params['attendance'][$employeeId]['device']['code'] = $serialNumber;
        $params['attendance'][$employeeId]['location']['lat'] = null;
        $params['attendance'][$employeeId]['location']['lng'] = null;
        $params['attendance'][$employeeId]['location']['image'][] = [];

        return $params;
    }

    private function buildAttendanceLog($serialNumber, $params, $employeeId, $timestamp, $machineStatus, $data)
    {
        return [
            'sn' => $serialNumber,
            'table' => $params['table'] ?? null,
            'stamp' => $params['Stamp'] ?? null,
            'employee_id' => intval($employeeId),
            'timestamp' => $timestamp,
            'status1' => $machineStatus,
            'status2' => $this->validateInteger($data[3]) ?? -1,
            'status3' => $this->validateInteger($data[4]) ?? -1,
            'status4' => $this->validateInteger($data[5]) ?? -1,
            'status5' => $this->validateInteger($data[6]) ?? -1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function updateMongoDB($model)
	{
		$data = $model->toArray(); 
	
		if (!empty($data['attendance'])) {
			$attendance = array_values($data['attendance']);
			$mdbData = array_map(function ($item) use ($data) {
				unset($data['attendance']);

				$blacklistData = array_filter($data['detail_info']['blacklist'] ?? [], function($blacklist) use ($item) {
					return $blacklist['employee_id'] === $item['employee_id'];
				});
	
				$data['detail_info']['blacklist'] = array_values($blacklistData);
	
				return array_merge($data, $item);
			}, $attendance);
	
			Yii::$app->mongodb->upsertMany($model, ['id', 'employee_id', 'date'], $mdbData);
		}
	}


    private function validateInteger($value)
    {
        return isset($value) && $value !== '' ? intval($value) : null;
    }
}
