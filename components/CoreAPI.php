<?php

namespace app\components;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\helpers\Constants;
use app\components\CustomException;

class CoreAPI 
{
	public function getTimestamp(): string
	{
		return gmdate('Y-m-d\TH:i:s\Z');
	}

	public function getUsername(): string
	{
		return Yii::$app->session->get('username') ?? 'system';
	}

	public function superAdmin(): bool
    {
        $roles = Yii::$app->session->get('roles', []);
        return !in_array('superadmin', $roles, true);
    }

	public function unavailableParams($model, ?array $params): object|bool
	{
		$allowedParams = [];
		unset($params['id']);

        $rules = $model->rules();

		foreach ($rules as $rule) {
			$allowedParams = array_merge($allowedParams, $rule[0]);
		}

		$unsupportedParams = array_diff_key($params, array_flip($allowedParams));
		
		if (!empty($unsupportedParams)) {
			foreach ($unsupportedParams as $key => $value) {
				$model->addError($key, Yii::t('core', 'invalidField', ['field' => $key]));
			}

			return $model;
		}

		return false;
	}

	public function unauthorizedAccess(?string $message = null): array
    {
        return [
            'code' => Yii::$app->response->statusCode = 401,
            'success' => false,
            'message' => Yii::t('rule', $message ?? 'unauthorizedAccess'),
            'errors' => [],
        ];
    }

	public function serverError(?string $message = null): array
    {
        return [
            'code' => Yii::$app->response->statusCode = 500,
            'success' => false,
            'message' => Yii::t('rule', $message ?? 'serverError'),
            'errors' => [],
        ];
    }

	public function setMongodbSyncFailed($model): void
	{
		if ($model->id !== null) {
			$model->sync = 1;
			$model->save(false);
		}
	}
}