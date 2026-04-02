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
use app\models\DeviceCommand;

class IclockController extends CoreController
{
    # TODO: Fix device_command status field

	public function behaviors()
    {
		$behaviors = parent::behaviors();

		$behaviors['verbs']['actions'] = array_merge(
			$behaviors['verbs']['actions'],
			[
				'cdata' => ['GET', 'POST'],
                'getrequest' => ['GET'],
                'devicecmd' => ['POST'],
                'ping' => ['GET'],
                'registry' => ['POST'],
			]
		);

        return $behaviors;
    }

    public function actionCdata()
    {
        $request = Yii::$app->getRequest();

        Yii::info([
            'method' => Yii::$app->request->method,
            'url' => Yii::$app->request->url,
            'body' => Yii::$app->request->rawBody,
        ], 'iclock');

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

        $params['name'] = Constants::ZKTECO_DEFAULT_NAME;
        $params['url'] = $request->getQueryParams();
        $params['code'] = $request->get('SN');

        $existingModel = $model::findOne(['code' => $params['code']]);
        if ($existingModel !== null) {
            return "Stamp=9999\nOpStamp=9999\nPhotoStamp=9999\nDelay=10\nRealtime=1\n";
        }
        
        CoreController::unavailableParams($model, $params);
		
		$params['status'] = Constants::STATUS_ACTIVE;
		$model->scenario = $scenario;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				return "Stamp=9999\nOpStamp=9999\nPhotoStamp=9999\nDelay=10\nRealtime=1\n";
			}
		}

		return CoreController::coreError($model);
	}

    protected function actionAttendance()
    {
        $model = new Attendance();
        $scenario = Constants::SCENARIO_CREATE;

        $request = Yii::$app->getRequest();
        $params = $request->getQueryParams();
        $params['system_date'] = date('Y-m-d');
        $serialNumber = $request->getQueryParam('SN');
        $content = preg_split('/\r\n|\r|\n/', $request->getRawBody());

        $table = $request->getQueryParam('table');
        if ($table === 'options') {
            return $this->handleOptions($request);
        }

        if ($table === 'OPERLOG') {
            return $this->handleOperLog($request);
        }

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
            $machineStatus = (int) $this->validateInteger($data[2]) ?? null;

            /**
             * CHECK IN  2\t2025-08-12 12:25:42\t0\t1\t0\t0\t0\t255\t0\t0\t\n
             * CHECK OUT 2\t2025-08-12 12:25:48\t1\t1\t0\t0\t0\t255\t0\t0\t\n
             */ 
            if (!array_key_exists($machineStatus, Constants::ZKTECO_STATUS)) {
                return "OK";
            }

            $params = $this->buildAttendanceParams($serialNumber, $params, $data, $employeeId, $machineStatus); 
            $this->attendanceLog($serialNumber, $params, $employeeId, $timestamp, $machineStatus, $data);
        }

        $params['date'] = $date;
        $params['dateMismatch'] = null;
        if (empty($params['date'])) {
			$model->addError('date', Yii::t('app', 'required', ['label' => $model->getAttributeLabel('date')]));
			return CoreController::coreError($model);
		}

        if ($params['date'] && isset($params['dayoff'])) {
			$params['dayoff'] = boolval($params['dayoff']);
			$params['system_date'] = $params['date'];
		} else {
			$params['dayoff'] = false;
		}

        // UNCOMMENT THIS FOR TESTING
		// $params['system_date'] = $params['date'];

        $existingModel = $model::findOne([
            'date' => $params['system_date'],
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
                        ['and', ['status' => Constants::STATUS_ACTIVE], ['<>', 'date', $params['system_date']]]
                    )->execute();
            }
        }

        if ($existingModel !== null) {
            $model = $existingModel;
        }
        
        $params['status'] = Constants::STATUS_ACTIVE;
        $model->scenario = $scenario;

        if ($model->load($params, '') && $model->validate()) {
            if ($model->save()) {	
                $this->updateMongoDB($model);
                return "OK";
                // return CoreController::coreSuccess($model);
            }
        }

        return "OK";
        // return CoreController::coreError($model);
    }

    /**
     * CREATE New User
     * DATA USER PIN=6	Name=Nandi	Pri=0	Passwd=	Card=[0000000000]	Grp=0	TZ=0
     * 
     * GET User Fingerprint data
     * DATA QUERY USERINFO PIN=1
     */

    public function actionGetrequest()
    {
        $sn = Yii::$app->request->get('SN');

        $deviceCommand = DeviceCommand::find()
            ->where(['sn' => $sn, 'status' => 'pending'])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        if ($deviceCommand) {
            Yii::$app->response->format = \yii\web\Response::FORMAT_RAW;
            Yii::$app->response->headers->set('Content-Type', 'text/plain');
            
            $command = "C:" . $deviceCommand->id . ":" . $deviceCommand->command;
            return $command;
        }

        return "OK";
    }

    public function actionDevicecmd()
    {
        $sn  = Yii::$app->request->get('SN');
        $raw = trim(Yii::$app->request->getRawBody());
    
        if (stripos($raw, 'Data=') === 0) {
            $payload = substr($raw, 5);
        } else {
            $payload = $raw;
        }
    
        if (ctype_xdigit($payload)) {
            $decoded = hex2bin($payload);
        } else {
            $decoded = $payload;
        }
    
        # Parse string: "ID=...&Return=...&CMD=..."
        parse_str(trim($decoded), $parsed);
    
        $id     = isset($parsed['ID']) ? (int) $parsed['ID'] : null;
        $return = isset($parsed['Return']) ? (int) $parsed['Return'] : null;
        $cmd    = isset($parsed['CMD']) ? $parsed['CMD'] : null;
    
        if ($id !== null) {
            $deviceCommand = DeviceCommand::findOne(['id' => $id, 'sn' => $sn]);
            if ($deviceCommand) {
                $deviceCommand->status   = ($return === 0) ? 'done' : 'error';
                $deviceCommand->response = $decoded;
                $deviceCommand->save(false);
            }
        }
    
        return "OK";
    }
    
    public function actionPing()
    {
		$request = Yii::$app->getRequest();
		$scenario = Constants::SCENARIO_ADMS_PING;

        $params['code'] = $request->get('SN');
		$model = CoreController::coreFindModelOne(new Device(), null, ['code' => $params['code']]);

		if ($model === null) {
			return CoreController::coreDataNotFound();
		}
        
		$model->scenario = Constants::SCENARIO_ADMS_PING;
		$model->save();

		return "OK";
	}

    private function buildAttendanceParams($serialNumber, $params, $data, $employeeId, $machineStatus)
    {
        $currentTime = date('H:i:s');
        $timestamp = date('H:i:s', strtotime($data[1])) ?? null;

        if (strtotime($currentTime) < strtotime(Constants::ATTENDANCE_NOON)) {
            $params['attendance'][$employeeId]['start_time'] = $timestamp;
            $params['attendance'][$employeeId]['end_time'] = null;
        } elseif (strtotime($currentTime) > strtotime(Constants::ATTENDANCE_AFTERNOON)) {
            $params['attendance'][$employeeId]['start_time'] = null;
            $params['attendance'][$employeeId]['end_time'] = $timestamp;
        } else {
            if ($machineStatus === Constants::ZKTECO_STATUS_IN) { 
                $params['attendance'][$employeeId]['start_time'] = $timestamp;
                $params['attendance'][$employeeId]['end_time'] = null;
            } elseif ($machineStatus === Constants::ZKTECO_STATUS_OUT) {
                $params['attendance'][$employeeId]['start_time'] = null;
                $params['attendance'][$employeeId]['end_time'] = $timestamp;
            }
        }

        $params['attendance'][$employeeId]['employee_id'] = $employeeId;
        $params['attendance'][$employeeId]['device']['code'] = $serialNumber;
        $params['attendance'][$employeeId]['location']['lat'] = null;
        $params['attendance'][$employeeId]['location']['lng'] = null;
        $params['attendance'][$employeeId]['location']['image'][] = [];

        return $params;
    }

    private function attendanceLog($serialNumber, $params, $employeeId, $timestamp, $machineStatus, $data)
    {
        $logs = [
            'params_table' => $params['table'] ?? null,
            'params_stamp' => $params['Stamp'] ?? null,
            'device_code' => $serialNumber,
            'employee_id' => intval($employeeId),
            'timestamp' => $timestamp,
            'status1' => $machineStatus,
            'status2' => $this->validateInteger($data[3]) ?? -1,
            'status3' => $this->validateInteger($data[4]) ?? -1,
            'status4' => $this->validateInteger($data[5]) ?? -1,
            'status5' => $this->validateInteger($data[6]) ?? -1,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        Yii::info(['attendanceLog' => $logs], 'attendance');
    }

    public function actionRegistry()
    {
        $request = Yii::$app->getRequest();
        $body = $request->getRawBody();

        Yii::info("Device registry received from SN={$request->getQueryParam('SN')}");
        Yii::info("Registry body: " . $body);

        return "OK";
    }

    protected function handleOptions($request)
    {
        $serialNumber = $request->getQueryParam('SN');
        $content = $request->getRawBody();

        Yii::info("Received OPTIONS from $serialNumber: " . $content, 'device');

        return Yii::$app->getResponse()
            ->setStatusCode(200)
            ->content = "OK";
    }

    protected function handleOperLog($request)
    {
        $serialNumber = $request->getQueryParam('SN') ?? 'UNKNOWN';
        $opStamp = $request->getQueryParam('OpStamp') ?? null;
        $content = preg_split('/\r\n|\r|\n/', $request->getRawBody());

        foreach ($content as $line) {
            if (empty($line)) {
                continue;
            }

            $data = explode("\t", $line);
            if (count($data) < 7 || stripos($line, 'OPLOG') !== 0) {
                Yii::warning("Invalid OPERLOG line: $line", 'device');
                continue;
            }

            $operationCode = intval($data[1]);
            $operationTime = $data[2];
            $object1 = $data[3] ?? null;
            $object2 = $data[4] ?? null;
            $object3 = $data[5] ?? null;
            $object4 = $data[6] ?? null;

            // Simpan ke log atau DB jika perlu
            Yii::info("OPLOG from $serialNumber [$operationTime]: Code=$operationCode, Obj1=$object1", 'device');
        }

        // Agar mesin tidak mengulang
        return Yii::$app->getResponse()
            ->setStatusCode(200)
            ->content = "OK";
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