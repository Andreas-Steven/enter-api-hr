<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\core\CoreController;
use app\models\search\GeneralAffairCategorySearch;
use app\models\GeneralAffairCategory; 

class GeneralAffairCategoryController extends CoreController
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

	public function actionIndex()
	{
		return CoreController::coreActionIndex();
	}

	public function actionData()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		$searchModel = new GeneralAffairCategorySearch();
		$dataProvider = $searchModel->search($params);

		if ($coreValidateProvider = CoreController::coreValidateProvider($dataProvider, $searchModel)) {
			return $coreValidateProvider;
		}

		return CoreController::coreData($dataProvider);
	}

    public function actionCreate()
	{
		$model = new GeneralAffairCategory();
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_CREATE;
	
		if ($coreUnavailableParams = CoreController::coreUnavailableParams($model, $params)) {
			return $coreUnavailableParams;
		}
	
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

		if ($coreValidateParams = CoreController::coreValidateParams($params, $scenario)) {
			return $coreValidateParams;
		}
		
		$model = CoreController::coreFindModelOne(new GeneralAffairCategory(), $params);
		
		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		if ($coreUnavailableParams = CoreController::coreUnavailableParams($model, $params)) {
			return $coreUnavailableParams;
        }

		$model->scenario = $scenario;

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
		$scenario = Constants::SCENARIO_DELETE;

		if ($coreValidateParams = CoreController::coreValidateParams($params, $scenario)) {
			return $coreValidateParams;
		}

		$model = CoreController::coreFindModelOne(new GeneralAffairCategory(), $params);

		if ($model === null) {
			return CoreController::coreDataNotFound();
		}
	
		$params['status'] = Constants::STATUS_DELETED;
		$model->scenario = $scenario;

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
