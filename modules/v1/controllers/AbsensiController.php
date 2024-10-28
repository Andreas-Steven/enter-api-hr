<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\components\CustomException;

use app\models\Absensi;
use app\helpers\Constants;
use app\controllers\CoreController;
use app\models\search\AbsensiSearch;

class AbsensiController extends CoreController
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

		$searchModel = new AbsensiSearch();
		$dataProvider = $searchModel->search($params);
		
		if ($coreValidateParams = CoreController::coreValidateParams($dataProvider)) {
			return $coreValidateParams;
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
			'data' => $dataProvider->getModels(),
		];

		return $data;
	}

	public function actionCreate()
	{
		$model = new Absensi();
		$params = Yii::$app->getRequest()->getBodyParams();
        
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($model->rules(), $params)) {
            return CoreController::coreValidateParams($unavailableParams);
        }

		if (!isset($params['date']) || $params['date'] === "") {
			return CoreController::coreBadRequest($model, Yii::t('app', 'dateEmpty'));
		}

		$model = Absensi::find()->where([
			'date' => $params['date']
		])->one();

		if ($model === null) {
			$model = new Absensi();
		}

		$params['status'] = Constants::STATUS_DRAFT;
		$model->scenario = Constants::SCENARIO_CREATE;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {
				return CoreController::coreSuccess($model, 'insertRecordSuccess');
			}
		}

		return CoreController::coreBadRequest($model, Yii::t('app', 'insertRecordFailed'));
	}
}
