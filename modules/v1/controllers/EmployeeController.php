<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\core\CoreController;
use app\models\Employee;
use app\models\search\EmployeeSearch;

class EmployeeController extends CoreController
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

		$searchModel = new EmployeeSearch();
		$dataProvider = $searchModel->search($params);

		if ($coreValidateProvider = CoreController::coreValidateProvider($dataProvider, $searchModel)) {
			return $coreValidateProvider;
		}

		return CoreController::coreData($dataProvider);
	}

	public function actionCreate()
	{
		$model = new Employee();
		$params = Yii::$app->getRequest()->getBodyParams();


        if ($coreUnavailableParams = CoreController::coreUnavailableParams($model, $params)) {
			return $coreUnavailableParams;
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
		$params['system_date'] = date('Y-m-d');

		if ($coreValidateParams = CoreController::coreValidateParams($params, Constants::SCENARIO_UPDATE)) {
			return $coreValidateParams;
		}
		
		$model = CoreController::coreFindModel(new Employee(), $params);
		
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

		$model = CoreController::coreFindModel(new Employee(), $params);

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
