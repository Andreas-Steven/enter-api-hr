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
		
		if (!isset($params['date']) || empty($params['date'])) {
			$model->addError('date', 'dateCannotBlank');
			return CoreController::coreError($model, Yii::t('app', 'dateCannotBlank'));
		}

		if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $params['date'])) {
			$model->addError('date', 'invalidDatetimeFormat');
			return CoreController::coreError($model, Yii::t('app', 'invalidDatetimeFormat'));
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
