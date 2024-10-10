<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\helpers\Constants;

use app\components\CustomException;
use app\controllers\CoreController;
use app\models\GeneralAffair;
use app\models\search\GeneralAffairSearch;


/**
 * GeneralAffairController implements the CRUD actions for GeneralAffair model.
 */
class GeneralAffairController extends CoreController
{
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
		$searchModel = new GeneralAffairSearch();
		$dataProvider = $searchModel->search($params);

		if ($coreValidateParams = CoreController::coreValidateParams($dataProvider)) {
			return $coreValidateParams;
		}

		$models = $dataProvider->getModels();
		$result = [];
	
		foreach ($models as $model) {
			$filteredItems = $model->item['items'];
	
			if (!empty($params['item']['items']['code'])) {
				$code = $params['item']['items']['code'];
				$filteredItems = array_filter($filteredItems, function ($item) use ($code) {
					return isset($item['code']) && $item['code'] == $code;
				});
			}
			
			if (!empty($params['item']['items']['department']['id'])) {
				$departmentId = $params['item']['items']['department']['id'];
				$filteredItems = array_filter($filteredItems, function ($item) use ($departmentId) {
					return isset($item['department']) && $item['department']['id'] == $departmentId;
				});
			}	

			if (isset($filteredItems)) {
				$itemData['items'] = array_values($filteredItems);
				$model->setAttribute('item', $itemData);
			}
	
			if (!empty($model->item['items'])) {
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
        $model = new GeneralAffair();
        $params = Yii::$app->getRequest()->getBodyParams();
		$rules = $model->rules();
		
		if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($rules, $params)) {
			return CoreController::coreValidateParams($unavailableParams);
        }
		
		if ($exisiting = Yii::$app->coreAPI->exisitingData($model, $params)) { 
			if ($callBack = $model->updateInsertItems($params, $exisiting)) {
				return CoreController::coreSuccess($callBack, 'insertRecordSuccess');
        	}
            
			return CoreController::coreBadRequest($callBack, Yii::t('app', 'valueExists'));
		}
		
		$model->scenario = Constants::SCENARIO_CREATE;
		if ($model->load($params, '') && $model->save()) {
			return CoreController::coreSuccess($model, 'insertRecordSuccess');
		}
		
        return CoreController::coreBadRequest($model, Yii::t('app', 'insertRecordFailed'));
    }

    public function actionUpdate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        if ($coreBadRequestNoParams = CoreController::coreBadRequestNoParams($params, Constants::SCENARIO_UPDATE)) {
			return $coreBadRequestNoParams;
		}

        $model = Yii::$app->coreAPI->findModel(GeneralAffair::className(), $params);
		
		if ($model == null) {
			return CoreController::coreDataNotFound();
		}
		
		$model->scenario = Constants::SCENARIO_UPDATE;
		$rules = $model->rules();

		if ($callBack = $model->updateItems($params) && $model->save()) {
			return CoreController::coreSuccess($model, 'updateRecordSuccess');
		}
		
        return CoreController::coreBadRequest($model, Yii::t('app', 'updateRecordFailed'));
    }

    public function actionDelete()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

		if ($coreBadRequestNoParams = CoreController::coreBadRequestNoParams($params, Constants::SCENARIO_DELETE)) {
			return $coreBadRequestNoParams;
		}

        $model = Yii::$app->coreAPI->findModel(GeneralAffair::className(), $params);
		
        if ($model === null) {
			return CoreController::coreDataNotFound();
		}
		
		$model->scenario = Constants::SCENARIO_DELETE;

        if ($model->deleteItems($params)) {
			return CoreController::coreSuccess($model, 'deleteRecordSuccess');
		}

        return CoreController::coreBadRequest($model, Yii::t('app', 'deleteRecordFailed'));
    }
}
