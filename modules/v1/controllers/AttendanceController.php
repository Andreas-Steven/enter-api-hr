<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\components\CustomException;

use app\models\Attendance;
use app\helpers\Constants;
use app\controllers\CoreController;
use app\models\search\AttendanceSearch;

class AttendanceController extends CoreController
{
	public $enableCsrfValidation = false;

	public function behaviors()
	{
		return parent::behaviors() + [
		   'authenticator' => [
				'class' => 'app\components\JwtBearerAuth',
				'except' => [
					Constants::AUTH_EXCEPTION,
				],
				'role' => Yii::$app->params['codeApp'],
			],
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => Constants::verbsAction(),
			],
		];
	}

	public function actionIndex()
	{
		return CoreController::coreActionIndex();
	}

	public function actionData()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		$searchModel = new AttendanceSearch();
		$dataProvider = $searchModel->search($params);
		
		if ($coreValidateParams = CoreController::coreValidateParams($dataProvider)) {
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
			return CoreController::coreBadRequest($model, Yii::t('rule', 'dateCannotBlank'));
		}

		// Date blacklist
		if ($params['date'] !== $params['system_date']) {
			$params['dateMismatch'] = $params['date'];
			$params['date'] = $params['system_date'];
		}

		$existingModel = Yii::$app->coreAPI->findModel($model, [], ['date' => $params['date'], 'status' => Constants::STATUS_ACTIVE]);

		// Update last date to Status Completed
		if ($existingModel === null) {
			$lastAttendance = Yii::$app->coreAPI->findModel($model, [], ['status' => Constants::STATUS_ACTIVE]);

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
				return CoreController::coreSuccess($model, 'insertRecordSuccess');
			}
		}

		return CoreController::coreBadRequest($model, Yii::t('rule', 'insertRecordFailed'));
	}

	public function actionUpdate()
	{
		return CoreController::coreActionIndex();
	}

	public function actionDelete()
	{
		return CoreController::coreActionIndex();
	}
}
