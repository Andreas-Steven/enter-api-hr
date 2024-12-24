<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\core\CoreController;
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
					'except' => Yii::$app->params['jwt']['except'],
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
		
		if ($coreValidateProvider = CoreController::coreValidateProvider($dataProvider, $searchModel)) {
			return $coreValidateProvider;
		}
		
		return CoreController::coreData($dataProvider);
	}

	public function actionCreate()
	{
		$model = new Attendance();
		$params = Yii::$app->getRequest()->getBodyParams();
		$params['system_date'] = date('Y-m-d');
		$params['dateMismatch'] = null;
		
		if ($coreUnavailableParams = CoreController::coreUnavailableParams($model, $params)) {
			return $coreUnavailableParams;
        }
		
		if (!isset($params['date']) || empty($params['date'])) {
			$model->addError('date', 'Date cannot be blank.');
			return CoreController::coreError($model);
		}

		$date = \DateTime::createFromFormat('Y-m-d', $params['date']);
		if (!$date || $date->format('Y-m-d') !== $params['date']) {
			$model->addError('date', 'Date format must be YYYY-MM-DD.');
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
		$model->scenario = Constants::SCENARIO_CREATE;

		if ($model->load($params, '') && $model->validate()) {
			// Temporary fix
			if ($model->save(false)) {
				return CoreController::coreSuccess($model);
			}

			// if ($model->save()) {
			// 	return CoreController::coreSuccess($model);
			// }
		}

		return CoreController::coreError($model);
	}
}
