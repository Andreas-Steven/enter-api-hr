<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use app\helpers\Constants;
use yii\filters\VerbFilter;
use app\core\CoreController;
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
					'except' => Yii::$app->params['jwt']['except'],
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
		
		CoreController::validateProvider($dataProvider, $searchModel);

		return CoreController::coreData($dataProvider);
	}

	public function actionCreate()
	{
		$model = new GeneralAffair();
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_CREATE;
		
		CoreController::unavailableParams($model, $params);

		$existingModel = $model::findOne([
			'product_id' => $params['product_id'],
			'location_id' => $params['location_id'],
			'status' => [Constants::STATUS_ACTIVE, Constants::STATUS_DRAFT],
		]);

		if ($existingModel !== null) {
			$model = $existingModel;
		}
		
		if ($existingModel === null) {
			$params['status'] = Constants::STATUS_DRAFT;
		}

		$model->scenario = $scenario;

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
		$scenario = Constants::SCENARIO_UPDATE;
		
		CoreController::validateParams($params, $scenario);

		$model = CoreController::coreFindModelOne(new GeneralAffair(), $params);
		
		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		CoreController::unavailableParams($model, $params);

		$model->scenario = $scenario;

		CoreController::superadmin($params);

		if ($model->load($params, '') && $model->validate()) {
			CoreController::emptyParams($model);

			if ($model->save()) {
				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}

	public function actionDelete()
	{
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_DELETE;

		CoreController::validateParams($params, $scenario);

		$model = CoreController::coreFindModelOne(new GeneralAffair(), $params);

		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		if (!isset($params['code'])) {
			$params['status'] = Constants::STATUS_DELETED;
		}

		$model->scenario = $scenario;

		CoreController::superadmin($params);

		if ($model->load($params, '') && $model->validate()) {
			if (isset($params['status'])) {
				CoreController::emptyParams($model);
			}
 
			if ($model->save()) {
				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}
}
