<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\components\CustomException;
use app\core\CoreController;
use app\models\AttendancePermission;
use app\models\search\AttendancePermissionSearch;

/**
 * AttendancePermissionController implements the CRUD actions for AttendancePermission model.
 */
class AttendancePermissionController extends CoreController
{
    public function behaviors()
    {
		$behaviors = parent::behaviors();

		$behaviors['verbs']['actions'] = array_merge(
			$behaviors['verbs']['actions'],
			[
				'index' => ['get'],
			]
		);

        return $behaviors;
    }

    public function actionData()
	{
		$params = Yii::$app->getRequest()->getBodyParams();

		$searchModel = new AttendancePermissionSearch();
		$dataProvider = $searchModel->search($params);

		CoreController::validateProvider($dataProvider, $searchModel);

		return CoreController::coreData($dataProvider);
	}

	public function actionCreate()
	{
		$model = new AttendancePermission();
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_CREATE;

		CoreController::unavailableParams($model, $params);

		$model->scenario = $scenario;
		$params['status'] = Constants::STATUS_DRAFT;
		$params['approved'] = null;
		$params['approved_note'] = null;

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
		
		$model = CoreController::coreFindModelOne(new AttendancePermission(), $params, [
			'status' => Constants::STATUS_DRAFT, 
			'approved' => [
				Constants::APPROVED_DEFAULT
			],
		]);
		
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

	public function actionApprove()
	{
		$params = Yii::$app->getRequest()->getBodyParams();
		$params['status'] = Constants::STATUS_ACTIVE;
		$scenario = Constants::SCENARIO_APPROVE;

		CoreController::validateParams($params, $scenario);
		
		$model = CoreController::coreFindModelOne(new AttendancePermission(), $params, [
			'status' => Constants::STATUS_ACTIVE, 
			'approved' => [
				Constants::APPROVED_DEFAULT,
				Constants::APPROVED_REJECT,
				Constants::APPROVED_APPROVE,
			],
		]);
		
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

	public function actionReject()
	{
		$params = Yii::$app->getRequest()->getBodyParams();
		$scenario = Constants::SCENARIO_REJECT;
		$params['status'] = Constants::STATUS_ACTIVE;
		$params['approved'] = Constants::APPROVED_REJECT;

		CoreController::validateParams($params, $scenario);
		
		$model = CoreController::coreFindModelOne(new AttendancePermission(), $params, [
			'status' => Constants::STATUS_ACTIVE, 
			'approved' => [
				Constants::APPROVED_DEFAULT
			],
		]);
		
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
		
		$model = CoreController::coreFindModelOne(new AttendancePermission(), $params);

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
