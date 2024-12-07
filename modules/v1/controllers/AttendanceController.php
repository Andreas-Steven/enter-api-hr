<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\controllers\CoreController;
use app\models\search\AttendanceSearch;
use app\models\Attendance;

class AttendanceController extends CoreController
{
	public function behaviors()
	{
		return array_merge(
			parent::behaviors(),
			[
				'authenticator' => [
					'class' => 'app\components\JwtBearerAuth',
					'except' => [
						Yii::$app->params['jwt']['authException'],
					],
					'role' => Yii::$app->params['codeApp'],
				],
			],
		);
	}

	public function actionData()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		$searchModel = new AttendanceSearch();
		$dataProvider = $searchModel->search($params);
		
		if ($coreValidateParams = CoreController::coreValidateParams($dataProvider, $searchModel)) {
			return $coreValidateParams;
		}
		
		return CoreController::coreData($dataProvider);
	}

	public function actionCreate()
	{
		$model = new Attendance();
		$params = Yii::$app->getRequest()->getBodyParams();
		$params['system_date'] = date('Y-m-d');
		$params['dateMismatch'] = null;

		if (!isset($params['date'])) {
			return CoreController::coreBadRequest__($model, Yii::t('rule', 'dateCannotBlank'));
		}

		// Date blacklist
		if ($params['date'] !== $params['system_date']) {
			$params['dateMismatch'] = $params['date'];
			$params['date'] = $params['system_date'];
		}

		$existingModel = CoreController::findModel($model, ['date' => $params['date'], 'status' => Constants::STATUS_ACTIVE]);
		// $existingModel = CoreController::findModel($model, $params);

		// Update last date to Status Completed
		if ($existingModel === null) {
			$lastAttendance = CoreController::findModel($model, ['status' => Constants::STATUS_ACTIVE]);
			// $lastAttendance = CoreController::findModel($model, $params);

			if ($lastAttendance !== null) {
				$params['status'] = Constants::STATUS_COMPLETED;
				$lastAttendance->scenario = Constants::SCENARIO_UPDATE;

				if ($lastAttendance->load($params, '') && $lastAttendance->validate()) {
					$lastAttendance->save();
				}
			}
		}

		if ($existingModel !== null) {
			$model = $existingModel;
		}

		if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($model->rules(), $params)) {
			return CoreController::coreValidateParams($unavailableParams);
		}

		$params['status'] = Constants::STATUS_ACTIVE;
		$model->scenario = Constants::SCENARIO_CREATE;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}
}
