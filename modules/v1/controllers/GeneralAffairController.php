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

class GeneralAffairController extends CoreController
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

		$searchModel = new GeneralAffairSearch();
		$dataProvider = $searchModel->search($params);
		
		if ($coreValidateParams = CoreController::coreValidateParams($dataProvider)) {
			return $coreValidateParams;
		}

		$models = $dataProvider->getModels();
		$formattedData = [];

		foreach ($models as $model) {
			$formattedData[] = [
				'id' => $model->id,
				'name' => $model->name,
				'category_id' => $model->category_id,
				'location_id' => $model->location_id,
				'status' => $model->status,
				'detail_info' => $model->detail_info,
				'item' => [
					'items' => json_decode($model->data),
					'quantity' => $model->item['quantity'] ?? null,
					'total_price' => $model->item['total_price'] ?? null,
				], 
			];
		}

		$data = [
			'code' => 200,
			'success' => true,
			'message' => Yii::t('app', 'success'), 
			'pagination' => [
				'page' => intval(isset($params['page']) ? $params['page'] : 1),
				'total' => $dataProvider->getTotalCount(),
				'display' => $dataProvider->getCount(),
			],
			'data' => $formattedData,
		];

		return $data;
	}

	public function actionCreate()
	{
		$model = new GeneralAffair();
		$params = Yii::$app->getRequest()->getBodyParams();
        
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($model->rules(), $params)) {
            return CoreController::coreValidateParams($unavailableParams);
        }

		if ((!isset($params['name']) || $params['name'] === "") && (!isset($params['location_id']) || $params['location_id'] === "")) {
			return CoreController::coreBadRequest($model, Yii::t('app', 'dateEmpty'));
		}

		$model = GeneralAffair::find()->where([
			'name' => $params['name'],
			'location_id' => $params['location_id'],
		])->one();

		if ($model === null) {
			$model = new GeneralAffair();
		}

		$params['status'] = Constants::STATUS_DRAFT;
		$model->scenario = Constants::SCENARIO_CREATE;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				return CoreController::coreSuccess($model->attributes, 'insertRecordSuccess');
			}
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
		$rules = $model->rules();
		
		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($rules, $params)) {
			return CoreController::coreValidateParams($unavailableParams);
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

		if ($coreBadRequestNoParams = CoreController::coreBadRequestNoParams($params, Constants::SCENARIO_DELETE)) {
			return $coreBadRequestNoParams;
		}
		
		$model = GeneralAffair::find()->where([
			'id' => $params['id'],
		])->one();
 
		if ($model === null) {
			return CoreController::coreDataNotFound();
		}
			
		if (!isset($params['code']) && empty($params['code'])) {
			$params['status'] = Constants::STATUS_DELETED;
		}

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
}
