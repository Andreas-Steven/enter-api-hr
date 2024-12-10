<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use app\helpers\Constants;
use yii\filters\VerbFilter;
use app\controllers\CoreController;
use app\components\CustomException;

use app\models\GeneralAffair;
use app\models\search\GeneralAffairSearch;

class GeneralAffairController extends CoreController
{
	public $enableCsrfValidation = false;

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

	public function actionIndex()
	{
		return CoreController::coreActionIndex();
	}

	public function actionData()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		$searchModel = new GeneralAffairSearch();
		$dataProvider = $searchModel->search($params);
		
		if ($coreValidateProvider = CoreController::coreValidateProvider($dataProvider, $searchModel)) {
			return $coreValidateProvider;
		}

		return CoreController::coreData($dataProvider);
	}

	public function actionCreate()
	{
		$model = new GeneralAffair();
		$params = Yii::$app->getRequest()->getBodyParams();
        
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($model->rules(), $params)) {
            return CoreController::coreValidateParams($unavailableParams);
        }

		$existingModel = $model::findOne([
			'product_id' => $params['product_id'],
			'location_id' => $params['location_id'],
			// 'status' => Constants::STATUS_ACTIVE,
		]);

		if ($existingModel !== null) {
			$model = $existingModel;
		}

		$params['status'] = Constants::STATUS_DRAFT;
		$model->scenario = Constants::SCENARIO_CREATE;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}

	public function actionUpdate()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		if ($coreValidateParams = CoreController::coreValidateParams($params, Constants::SCENARIO_UPDATE)) {
			return $coreValidateParams;
		}
		
		$model = CoreController::findModel(new GeneralAffair(), $params);
		
		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		if ($coreUnavailableParams = CoreController::coreUnavailableParams($model, $params)) {
			return $coreUnavailableParams;
        }

		$model->scenario = Constants::SCENARIO_UPDATE;

		if ($coreSuperadmin = CoreController::coreSuperadmin($params)) {
			return $coreSuperadmin;
		}

		if ($model->load($params, '') && $model->validate()) {
			if ($coreEmptyParams = CoreController::coreEmptyParams($model)) {
                return $coreEmptyParams;
            }

			if ($model->save()) {
				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}

	public function actionDelete()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		if ($coreValidateParams = CoreController::coreValidateParams($params, Constants::SCENARIO_DELETE)) {
			return $coreValidateParams;
		}

		$model = CoreController::findModel(new GeneralAffair(), $params);

		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		if (!isset($params['code'])) {
			$params['status'] = Constants::STATUS_DELETED;
		}

		$model->scenario = Constants::SCENARIO_DELETE;

		if ($coreSuperadmin = CoreController::coreSuperadmin($params)) {
			return $coreSuperadmin;
		}

		if ($model->load($params, '') && $model->validate()) {
			if ($coreEmptyParams = CoreController::coreEmptyParams($model)) {
                return $coreEmptyParams;
            }

			if ($model->save()) {
				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}
}
