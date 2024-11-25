<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use app\helpers\Constants;
use yii\filters\VerbFilter;
use app\controllers\CoreController;
use app\components\CustomException;

use app\models\Division;
use app\models\search\DivisionSearch;

class DivisionController extends CoreController
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

		$searchModel = new DivisionSearch();
		$dataProvider = $searchModel->search($params);

		if ($coreValidateParams = CoreController::coreValidateParams($dataProvider)) {
			return $coreValidateParams;
		}

		return CoreController::coreData($dataProvider);
	}

    public function actionCreate()
    {
        $model = new Division();
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

        if ($coreBadRequestNoParams = CoreController::coreBadRequestNoParams($params, Constants::SCENARIO_UPDATE)) {
			return $coreBadRequestNoParams;
		}

        $model = Yii::$app->coreAPI->findModel(Division::className(), $params);
		$rules = $model->rules();

        if ($model === null) {
			return CoreController::coreDataNotFound();
		}

        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($rules, $params)) {
			return CoreController::coreValidateParams($unavailableParams);
        }

        if (isset($params['name'])) {
            $params['location_id'] = $model->id;
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

		if ($coreBadRequest = CoreController::coreBadRequest($params, Constants::SCENARIO_DELETE)) {
			return $coreBadRequest;
		}

		$model = Yii::$app->coreAPI->findModel(new Division(), $params);

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
