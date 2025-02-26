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

		if ($coreValidateProvider = CoreController::coreValidateProvider($dataProvider, $searchModel)) {
			return $coreValidateProvider;
		}

		return CoreController::coreData($dataProvider);
	}

	public function actionCreate()
	{
		$model = new AttendancePermission();
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

		if ($coreValidateParams = CoreController::coreValidateParams($params, Constants::SCENARIO_UPDATE)) {
			return $coreValidateParams;
		}
		
		$model = CoreController::coreFindModel(new AttendancePermission(), $params, [
			'status' => Constants::STATUS_DRAFT, 
			'approved' => [
				Constants::APPROVED_DEFAULT
			],
		]);
		
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

	public function actionApprove()
	{
		$params = Yii::$app->getRequest()->getBodyParams();
		$params['status'] = Constants::STATUS_ACTIVE;

		if ($coreValidateParams = CoreController::coreValidateParams($params, Constants::SCENARIO_UPDATE)) {
			return $coreValidateParams;
		}
		
		$model = CoreController::coreFindModel(new AttendancePermission(), $params, [
			'status' => Constants::STATUS_ACTIVE, 
			'approved' => [
				Constants::APPROVED_DEFAULT
			],
		]);
		
		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		if ($coreUnavailableParams = CoreController::coreUnavailableParams($model, $params)) {
			return $coreUnavailableParams;
        }

		$model->scenario = Constants::SCENARIO_APPROVE;

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

	public function actionReject()
	{
		$params = Yii::$app->getRequest()->getBodyParams();
		$params['status'] = Constants::STATUS_ACTIVE;

		if ($coreValidateParams = CoreController::coreValidateParams($params, Constants::SCENARIO_UPDATE)) {
			return $coreValidateParams;
		}
		
		$model = CoreController::coreFindModel(new AttendancePermission(), $params, [
			'status' => Constants::STATUS_ACTIVE, 
			'approved' => [
				Constants::APPROVED_DEFAULT
			],
		]);
		
		if ($model === null) {
			return CoreController::coreDataNotFound();
		}

		if ($coreUnavailableParams = CoreController::coreUnavailableParams($model, $params)) {
			return $coreUnavailableParams;
        }

		$model->scenario = Constants::SCENARIO_REJECT;

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

		$model = CoreController::coreFindModel(new AttendancePermission(), $params);

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
