<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\controllers\CoreController;
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
		
		$searchModel = new EmployeePositionSearch();
		$dataProvider = $searchModel->search($params);

		if ($coreValidateParams = CoreController::coreValidateParams($dataProvider, $searchModel)) {
			return $coreValidateParams;
		}

		return CoreController::coreData($dataProvider);
	}

	public function actionCreate()
	{
		$model = new EmployeePosition();
		$params = Yii::$app->getRequest()->getBodyParams();

        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($model->rules(), $params)) {
			return CoreController::coreValidateParams($unavailableParams);
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

		if ($coreBadRequest = CoreController::coreBadRequest($params, Constants::SCENARIO_UPDATE)) {
			return $coreBadRequest;
		}
		
		$model = CoreController::findModel(new EmployeePosition(), $params);
		
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

		if ($coreBadRequest = CoreController::coreBadRequest($params, Constants::SCENARIO_DELETE)) {
			return $coreBadRequest;
		}

		$model = CoreController::findModel(new EmployeePosition(), $params);

		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		$params['status'] = Constants::STATUS_DELETED;
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
