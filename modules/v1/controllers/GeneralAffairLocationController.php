<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use app\helpers\Constants;
use yii\filters\VerbFilter;
use app\controllers\CoreController;
use app\components\CustomException;

use app\models\GeneralAffairLocation;
use app\models\search\GeneralAffairLocationSearch;

class GeneralAffairLocationController extends CoreController
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

		$searchModel = new GeneralAffairLocationSearch();
		$dataProvider = $searchModel->search($params);

		if ($coreValidateParams = CoreController::coreValidateParams($dataProvider)) {
			return $coreValidateParams;
		}

		return CoreController::coreData($dataProvider);
	}

    public function actionCreate()
    {
        $model = new GeneralAffairLocation();
		$params = Yii::$app->getRequest()->getBodyParams();

        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($model->rules(), $params)) {
			return CoreController::coreValidateParams($unavailableParams);
        }

		$params['status'] = Constants::STATUS_DRAFT;
		$model->scenario = Constants::SCENARIO_CREATE;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				return CoreController::coreSuccess($model, 'insertRecordSuccess');
			}
		}

		return CoreController::coreValidationFailed($model, 'insertRecordFailed');
    }

    public function actionUpdate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        if ($coreBadRequest = CoreController::coreBadRequest($params, Constants::SCENARIO_UPDATE)) {
			return $coreBadRequest;
		}

		$model = Yii::$app->coreAPI->findModel(new GeneralAffairLocation(), $params);
		
		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($model->rules(), $params)) {
			return CoreController::coreValidateParams($unavailableParams);
        }

		$model->scenario = Constants::SCENARIO_UPDATE;

		if ($coreSuperadmin = CoreController::coreSuperadmin($params)) {
			return $coreSuperadmin;
		}

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				return CoreController::coreSuccess($model, 'updateRecordSuccess');
			}
		}

		return CoreController::coreValidationFailed($model, 'updateRecordFailed');
    }

	public function actionDelete()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		if ($coreBadRequest = CoreController::coreBadRequest($params, Constants::SCENARIO_DELETE)) {
			return $coreBadRequest;
		}

		$model = Yii::$app->coreAPI->findModel(new GeneralAffairLocation(), $params);

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

		return CoreController::coreValidationFailed($model, 'deleteRecordFailed');
	}
}
