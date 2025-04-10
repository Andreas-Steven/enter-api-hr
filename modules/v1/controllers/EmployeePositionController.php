<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\core\CoreController;
use app\models\EmployeePosition;
use app\models\search\EmployeePositionSearch;

/**
 * EmployeePositionController implements the CRUD actions for EmployeePosition model.
 */
class EmployeePositionController extends CoreController
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
		
		$searchModel = new EmployeePositionSearch();
		$dataProvider = $searchModel->search($params);

		CoreController::validateProvider($dataProvider, $searchModel);

		return CoreController::coreData($dataProvider);
	}

	public function actionCreate()
	{
		$model = new EmployeePosition();
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_CREATE;

        CoreController::unavailableParams($model, $params);

		$params['status'] = Constants::STATUS_DRAFT;
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

		$model = CoreController::coreFindModelOne(new EmployeePosition(), $params);
		
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

		$model = CoreController::coreFindModelOne(new EmployeePosition(), $params);

		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		$params['status'] = Constants::STATUS_DELETED;
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
}
