<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\web\Response;
use yii\rest\Controller;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\core\CoreController;
use app\models\search\AttendanceSearch;
use app\models\Attendance;
use yii\helpers\ArrayHelper;

class AttendanceController extends CoreController
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

		$searchModel = new AttendanceSearch();
		$dataProvider = $searchModel->search($params);
		
		if ($coreValidateProvider = CoreController::coreValidateProvider($dataProvider, $searchModel)) {
			return $coreValidateProvider;
		}

		return CoreController::coreData($dataProvider);
	}
	
	public function actionCreate()
	{
		$model = new Attendance();
		$params = Yii::$app->getRequest()->getBodyParams();
		$params['system_date'] = date('Y-m-d');
		$params['dateMismatch'] = null;

		if ($coreUnavailableParams = CoreController::coreUnavailableParams($model, $params)) {
			return $coreUnavailableParams;
        }

		if (empty($params['date'])) {
			$model->addError('date', Yii::t('core', 'required', ['label' => $model->getAttributeLabel('date')]));
			return CoreController::coreError($model);
		}

		// UNCOMMENT THIS FOR TESTING
		// $params['system_date'] = $params['date'];

		if (!empty($params['dayoff']) && boolval($params['dayoff'])) {
			$params['system_date'] = $params['date'];
		}

		$date = \DateTime::createFromFormat('Y-m-d', $params['date']);
		if (!$date || $date->format('Y-m-d') !== $params['date']) {
			$model->addError('date', Yii::t('app', 'dateInvalid', ['field' => $model->getAttributeLabel('date')]));
			return CoreController::coreError($model);
		}

		$existingModel = $model::findOne([
			'date' => $params['system_date'],
			'status' => Constants::STATUS_ACTIVE,
		]);

		if (empty($existingModel)) {
			$updateLastAttendance = $model::findAll([
				'status' => Constants::STATUS_ACTIVE,
			]);

			if (!empty($updateLastAttendance)) {
				foreach ($updateLastAttendance as $attendanceModel) {
					$attendanceModel->scenario = Constants::SCENARIO_UPDATE;
					$attendanceModel->save();
				}

				Yii::$app->db->createCommand()
					->update(
						Attendance::tableName(), 
						['status' => Constants::STATUS_COMPLETED], 
						['and', ['status' => Constants::STATUS_ACTIVE], ['<>', 'date', $params['system_date']]]
					)->execute();
			}
		}		

		if ($existingModel !== null) {
			$model = $existingModel;
		}

		$params['status'] = Constants::STATUS_ACTIVE;
		$model->scenario = Constants::SCENARIO_CREATE;

		if ($model->load($params, '') && $model->validate()) {
			if ($model->save()) {	
				$this->updateMongoDB($model);

				return CoreController::coreSuccess($model);
			}
		}

		return CoreController::coreError($model);
	}

	public function actionList()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $searchModel = new AttendanceSearch();
        $searchModel->load($params);
        $dataProvider = $searchModel->mongodbSearch($params);

        if ($coreValidateProvider = CoreController::coreValidateProvider($dataProvider, $searchModel)) {
            return $coreValidateProvider;
        }

        return CoreController::coreData($dataProvider);
    }

	public function actionReport()
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $searchModel = new AttendanceSearch();
        $searchModel->load($params);
		$dataProvider = $searchModel->attendanceReport($params);

        if ($coreValidateProvider = CoreController::coreValidateProvider($dataProvider, $searchModel)) {
            return $coreValidateProvider;
        }

        return CoreController::coreData($dataProvider);
    }

	public function updateMongoDB($model)
	{
		$data = $model->toArray(); 
	
		if (!empty($data['attendance'])) {
			$attendance = array_values($data['attendance']);
			$mdbData = array_map(function ($item) use ($data) {
				unset($data['attendance']);

				$blacklistData = array_filter($data['detail_info']['blacklist'] ?? [], function($blacklist) use ($item) {
					return $blacklist['employee_id'] === $item['employee_id'];
				});
	
				$data['detail_info']['blacklist'] = array_values($blacklistData);
	
				return array_merge($data, $item);
			}, $attendance);
	
			Yii::$app->mongodb->upsertMany($model, ['id', 'employee_id', 'date'], $mdbData);
		}
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
			$employee->save();
		}
	}
}
