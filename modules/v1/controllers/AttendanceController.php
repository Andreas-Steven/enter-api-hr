<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use yii\helpers\ArrayHelper;
use app\core\CoreController;
use app\models\Attendance;
use app\models\search\AttendanceSearch;
use app\models\search\AttendancePermissionSearch;

class AttendanceController extends CoreController
{
	public function behaviors()
    {
		$behaviors = parent::behaviors();

		$behaviors['verbs']['actions'] = array_merge(
			$behaviors['verbs']['actions'],
			[
				'index' => ['get'],
				'report' => ['post'],
			]
		);

        return $behaviors;
    }

	public function actionData()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		$searchModel = new AttendanceSearch();
		$dataProvider = $searchModel->search($params);
		
		CoreController::validateProvider($dataProvider, $searchModel);

		return CoreController::coreData($dataProvider);
	}
	
	public function actionCreate()
	{
		$model = new Attendance();
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_CREATE;
		$params['system_date'] = date('Y-m-d');
		$params['dateMismatch'] = null;

		CoreController::unavailableParams($model, $params);

		if (empty($params['date'])) {
			$model->addError('date', Yii::t('app', 'required', ['label' => $model->getAttributeLabel('date')]));
			return CoreController::coreError($model);
		}

		// UNCOMMENT THIS FOR TESTING
		// $params['system_date'] = $params['date'];
		
		if ($params['date'] && isset($params['dayoff'])) {
			$params['dayoff'] = boolval($params['dayoff']);
			$params['system_date'] = $params['date'];
		} else {
			$params['dayoff'] = false;
		}

		$date = \DateTime::createFromFormat('Y-m-d', $params['date']);
		if (!$date || $date->format('Y-m-d') !== $params['date']) {
			$model->addError('date', Yii::t('app', 'dateInvalid', ['label' => $model->getAttributeLabel('date')]));
			return CoreController::coreError($model);
		}

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
						Attendance::tableName(), 
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

				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}

	public function actionShow()
	{
		$params = Yii::$app->getRequest()->getBodyParams();
		$params['employee_id'] = Yii::$app->session->get('userid') ?? $params['employee_id'] ?? 1;

		if (empty($params['date'])) {
			$currentDate = new \DateTime();
			$currentDay = (int) $currentDate->format('d');
			
			if ($currentDay >= 26) {
				$startDate = (new \DateTime())->setDate(
					(int) $currentDate->format('Y'), 
					(int) $currentDate->format('m'), 
					26
				);
				$endDate = (new \DateTime())->setDate(
					(int) $currentDate->format('Y'),  
					(int) $currentDate->format('m'), 
					25
				)->modify('+1 month');
			} else {
				$startDate = (new \DateTime())->setDate(
					(int) $currentDate->format('Y'), 
					(int) $currentDate->format('m'), 
					26
				)->modify('-1 month');
				$endDate = (new \DateTime())->setDate(
					(int) $currentDate->format('Y'), 
					(int) $currentDate->format('m'), 
					25
				);
			}
			
			$params['date'] = $startDate->format('Y-m-d') . ',' . $endDate->format('Y-m-d');
		}
		
		$searchModel = new AttendanceSearch();
		$permissionSearchModel = new AttendancePermissionSearch();
		$dataProvider = $searchModel->search($params);
		$permissionDataProvider = $permissionSearchModel->search($params);
		
		CoreController::validateProvider($dataProvider, $searchModel);
		CoreController::validateProvider($permissionDataProvider, $permissionSearchModel);

		return ArrayHelper::merge(
			CoreController::coreData($dataProvider), 
			['permission' => $permissionDataProvider]
		);
	}

	public function actionList()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $searchModel = new AttendanceSearch();
        $searchModel->load($params);
        $dataProvider = $searchModel->mongodbSearch($params);

        CoreController::validateProvider($dataProvider, $searchModel);

        return CoreController::coreData($dataProvider);
    }

	public function actionReport()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $searchModel = new AttendanceSearch();
        $searchModel->load($params);
		$dataProvider = $searchModel->attendanceReport($params);

        CoreController::validateProvider($dataProvider, $searchModel);

        return CoreController::coreData($dataProvider);
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
}
