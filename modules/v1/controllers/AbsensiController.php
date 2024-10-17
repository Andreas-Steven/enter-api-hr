<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\components\CustomException;

use app\models\Absensi;
use app\helpers\Constants;
use app\controllers\CoreController;
use app\models\search\AbsensiSearch;

/**
 * AbsensiController implements the CRUD actions for Absensi model.
 */
class AbsensiController extends CoreController
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
		$searchModel = new AbsensiSearch();
		$dataProvider = $searchModel->search($params);

		if ($coreValidateParams = CoreController::coreValidateParams($dataProvider)) {
			return $coreValidateParams;
		}

		$models = $dataProvider->getModels();
		$result = [];
	
		foreach ($models as $model) {
			$filters = [];
            if (!empty($params['attendance']['employee_id'])) {
                $filters['employee_id'] = $params['attendance']['employee_id'];
            }
            if (!empty($params['attendance']['department']['id'])) {
                $filters['department.id'] = $params['attendance']['department']['id'];
            }
            $filteredAttendance = Yii::$app->coreAPI->filterAttendances($model->attendance, $filters);

            if (!empty($filteredAttendance)) {
                $model->attendance = array_values($filteredAttendance);
                $result[] = $model;
            }
		}

		return [
			'code' => 200,
			'success' => true,
			'message' => Yii::t('app', 'success'),
			'data' => !empty($result) ? $result : null,
		];
	}

	public function actionCreate()
	{
		$model = new Absensi();
		$params = Yii::$app->getRequest()->getBodyParams();
		$rules = $model->rules();
        
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($rules, $params)) {
            return CoreController::coreValidateParams($unavailableParams);
        }

		if ($model === null) {
            return CoreController::coreDataNotFound();
		}

		$model->scenario = Constants::SCENARIO_CREATE;

		if ($model->load($params, '') && $model->validate()) {
			if ($exisiting = Yii::$app->coreAPI->exisitingData($model, $params)) {
				if ($callBack = $model->updateInsertAttendance($params, $exisiting)) {
					return CoreController::coreSuccess($callBack, 'insertRecordSuccess');
				}
	
				return CoreController::coreSuccess($callBack, 'insertRecordFailed');
			}
	
			if ($model->save()) {
				return CoreController::coreSuccess($model, 'insertRecordSuccess');
			}
		}

		return CoreController::coreBadRequest($model, Yii::t('app', 'insertRecordFailed'));
	}
	
	public function actionUpdate()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		// Search for ID
		$searchModel = new AbsensiSearch();
		$dataProvider = $searchModel->search($params);
		$model = $dataProvider->getModels();
		
		if (empty($model)) {
			return CoreController::coreDataNotFound();
		}

		$absensiModel = $model[0];
		$params['id'] = $absensiModel->id;
		
		if ($coreBadRequestNoParams = CoreController::coreBadRequestNoParams($params, Constants::SCENARIO_UPDATE)) {		
			return $coreBadRequestNoParams;
		}
		
		$model = Yii::$app->coreAPI->findModel(Absensi::className(), $params);
		
		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		$rules = $model->rules();
		$model->scenario = Constants::SCENARIO_UPDATE;
		
		if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($rules, $params)) {
			return CoreController::coreValidateParams($unavailableParams);
		}

		if ($coreSuperadmin = CoreController::coreSuperadmin($params)) {
			return $coreSuperadmin;
		}

		if ($callback = $model->updateData($params)) {
			return CoreController::coreSuccess($callback, 'updateRecordSuccess');
		}

		return CoreController::coreBadRequest($model, Yii::t('app', 'updateRecordFailed'));
	}

	/*
	public function actionDelete()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		if ($coreBadRequestNoParams = CoreController::coreBadRequestNoParams($params, Constants::SCENARIO_DELETE)) {
			return $coreBadRequestNoParams;
		}

		$model = Yii::$app->coreAPI->findModel(Brand::className(), $params);

		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		$params['status'] = Constants::STATUS_DELETED;
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
	*/
}
