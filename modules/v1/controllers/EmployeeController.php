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

		CoreController::validateProvider($dataProvider, $searchModel);

		return CoreController::coreData($dataProvider);
	}

	public function actionCreate()
	{
		$model = new Employee();
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_CREATE;

        CoreController::unavailableParams($model, $params);

		$model->scenario = $scenario;
		$params['status'] = Constants::STATUS_DRAFT;
		$params['work_time_id'] = null;

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
		$scenario = Constants::SCENARIO_UPDATE;

		CoreController::validateParams($params, $scenario);
		
		$model = CoreController::coreFindModelOne(new Employee(), $params);
		
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

		$model = CoreController::coreFindModelOne(new Employee(), $params);

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

	public function actionResign()
	{
		$model = new Employee();
		$system_date = date('Y-m-d');

		$employees = $model::find()
			->where(['<', 'date_resign', $system_date])
			->andWhere(['status' => Constants::STATUS_MAINTENANCE])
			->all();
	
		foreach ($employees as $employee) {
			$employee->scenario = Constants::SCENARIO_UPDATE;
			$employee->status = Constants::STATUS_COMPLETED;
			$employee->save(false);
		}
	}
}
