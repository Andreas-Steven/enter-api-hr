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
			],
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => Constants::verbsAction(),
			],
		];
	}

	public function actionIndex()
	{
		$data = [
			'code' => 200,
			'success' => true,
			'message' => Yii::$app->params['titleService'].' '.Yii::$app->params['serviceVersion'],  
		];

		return $data;
	}

	public function actionData()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		$searchModel = new AttendanceSearch();
		$dataProvider = $searchModel->search($params);
		$totalAttendanceCount = $dataProvider['totalAttendanceCount'] ?? 0;
		$displayAttendanceCount = $dataProvider['displayAttendanceCount'] ?? 0;
		
		if ($coreValidateParams = CoreController::coreValidateParams($dataProvider)) {
			return $coreValidateParams;
		}
		
		$data = [
			'code' => 200,
			'success' => true,
			'message' => Yii::t('app', 'success'), 
			'pagination' => [
				'page' => intval(isset($params['page']) ? $params['page'] : 1),
				'total' => $totalAttendanceCount,
				'display' => $displayAttendanceCount,
			],
			'data' => $dataProvider['dataProvider'],
		];

		return $data;
	}

	public function actionCreate()
	{
		$model = new Attendance();
		$params = Yii::$app->getRequest()->getBodyParams();
        
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($model->rules(), $params)) {
            return CoreController::coreValidateParams($unavailableParams);
        }

		if (!isset($params['date']) || $params['date'] === "") {
			return CoreController::coreBadRequest($model, Yii::t('app', 'dateEmpty'));
		}

		$model = Attendance::find()->where([
			'date' => $params['date']
		])->one();

		if ($model === null) {
			$model = new Attendance();
		}

		$params['status'] = Constants::STATUS_DRAFT;
		$model->scenario = Constants::SCENARIO_CREATE;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				return CoreController::coreSuccess($model, 'insertRecordSuccess');
			}
		}

		return CoreController::coreBadRequest($model, Yii::t('app', 'insertRecordFailed'));
	}

	public function actionUpdate()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		if ($coreBadRequestNoParams = CoreController::coreBadRequestNoParams($params, Constants::SCENARIO_UPDATE)) {
			return $coreBadRequestNoParams;
		}
		
		$model = Yii::$app->coreAPI->findModel(Attendance::className(), $params);
		$rules = $model->rules();
		
		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($rules, $params)) {
			return CoreController::coreValidateParams($unavailableParams);
        }

		if ($coreSuperadmin = CoreController::coreSuperadmin($params)) {
			return $coreSuperadmin;
		}

		$model->scenario = Constants::SCENARIO_UPDATE;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				return CoreController::coreSuccess($model, 'updateRecordSuccess');
			}
		}

		return CoreController::coreBadRequest($model, Yii::t('app', 'updateRecordFailed'));
	}

	public function actionDelete()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		if ($coreBadRequestNoParams = CoreController::coreBadRequestNoParams($params, Constants::SCENARIO_DELETE)) {
			return $coreBadRequestNoParams;
		}

		$model = Yii::$app->coreAPI->findModel(Attendance::className(), $params);

		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		if (!isset($params['code']) || empty($params['code'])) {
			$params['status'] = Constants::STATUS_DELETED;
		}

		$model->scenario = Constants::SCENARIO_DELETE;

		if ($coreSuperadmin = CoreController::coreSuperadmin($params)) {
			return $coreSuperadmin;
		}

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				return CoreController::coreSuccess($model, 'deleteRecordSuccess');
			}
		}

		return CoreController::coreBadRequest($model, Yii::t('app', 'deleteRecordFailed'));
	}
}
