<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\components\CustomException;
use app\helpers\Constants;

class CoreController extends Controller
{
	public $enableCsrfValidation;

    public function behaviors()
    {
		$this->enableCsrfValidation = Yii::$app->params['request']['enableCsrfValidation'];
        
        return [
            'contentNegotiator' => [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'corsFilter' => [
                'class' => '\yii\filters\Cors',
                'cors' => [
                    'Origin' => Yii::$app->params['cors']['origins'], 
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Request-Origin' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Request-Method' => Yii::$app->params['cors']['requestMethods'],
                    'Access-Control-Allow-Headers' => Yii::$app->params['cors']['allowHeaders'],
                ],
            ],
            'verbs' => [
				'class' => 'yii\filters\VerbFilter',
				'actions' => Yii::$app->params['verbsAction'],
			],
        ];
    }

    public function actionIndex()
	{
		return [
			'code' => Yii::$app->response->statusCode = 200,
			'success' => true,
			'message' => Yii::$app->params['titleService'].' '.Yii::$app->params['serviceVersion'],
            'data' => [
                [
                    'language' => Yii::$app->language,
                    'version' => Yii::$app->params['serviceVersion'],
                ]
            ],
		];
	}

    public function coreActionIndex()
	{
		return [
			'code' => Yii::$app->response->statusCode = 200,
			'success' => true,
			'message' => Yii::$app->params['titleService'].' '.Yii::$app->params['serviceVersion'],
            'data' => [
                [
                    'language' => Yii::$app->language,
                    'version' => Yii::$app->params['serviceVersion'],
                ]
            ],
		];
	}

    public function actionError()
    {
        return [
            'status' => Yii::$app->errorHandler->exception->statusCode,
            'success' => false,
            'message' => Yii::$app->errorHandler->exception->getMessage(),
            'errors' => [],
        ];
    }

    public function errorHandler($exception)
    {
        $response = new Response();
        $response->setStatusCode($exception->getCode());
        $response->data = [
            'code' => $exception->getCode(),
            'success' => false,
            'message' => 'Custom error message',
        ];
        return $response;
    }

    public function coreError($model, ?string $message = null): array 
    {
        return [
            'code' => Yii::$app->response->statusCode = 422,
            'success' => false,
            'message' => Yii::t('core', $message ?? "{$model->scenario}RecordFailed"),
            'errors' => isset($model->errors) ? $model : [],
        ];
    }

    public function coreSuccess($model, ?array $data=null, ?string $message = null): array
    {
        $response[] = $data ?? $model;
        $message = $message ?? Yii::t('core', "{$model->scenario}RecordSuccess");
        return [
            'code' => Yii::$app->response->statusCode = 200,
            'success' => true,
            'message' => $message,
            'data' => $response,
        ];
    }

    public function coreData($dataProvider): array
    {
        return [
            'code' => 200,
            'success' => true,
            'message' => Yii::t('app', 'success'),
            'pagination' => [
                'page' => $dataProvider->pagination->page + 1,
                'totalCount' => $dataProvider->totalCount,
                'total' => max($dataProvider->count, 0),
                'display' => $dataProvider->count,
            ],
            'data' => $dataProvider,
        ];
    }

    public function coreException($model, ?string $message = null): array 
    {
        return [
            'code' => Yii::$app->response->statusCode = 422,
            'success' => false,
            'message' => $message ?? Yii::t('core', 'exceptionOccured'),
            'errors' => $model->errors ?? [],
        ];
    }

	public function findModel($model, ?array $params, array $where = []): ?object
	{
		if (isset($params['id'])) {
			$query = $model::find()->where(array_merge(['id' => $params['id']], $where));
			if (($modelInstance = $query->one()) !== null) {
				return $modelInstance;
			}
		}

		return null;
	}

    public function coreBadRequest(?array $params, string $scenario): array|bool
    {
        $errors = [];

        if (isset($params['id'])) {
            $id = $params['id'];
            unset($params['id']);

            if ($scenario === Constants::SCENARIO_UPDATE && empty($params)) {
                $errors[] = [
                    'field' => 'id',
                    'message' => Yii::t('core', 'emptyParams'),
                ];
            }

            if (!is_numeric($id) || intval($id) != $id) {
                $errors[] = [
                    'field' => 'id',
                    'message' => Yii::t('core', 'integer', ['field' => 'Id']),
                ];
            }
        } else {
            $errors[] = [
                'field' => 'id',
                'message' => Yii::t('core', 'required', ['field' => 'Id']),
            ];
        }

        if (!empty($errors)) {
            return [
                'code' => Yii::$app->response->statusCode = 400,
                'success' => false,
                'message' => Yii::t('core', 'badRequest'),
                'errors' => $errors,
            ];
        }

        return false;
    }

    public function coreDataNotFound(): array
    {
        try {
            throw new \Exception(Yii::t('core', 'dataNotFound'));
        } catch (\Exception $e) {
            return [
                'code' => Yii::$app->response->statusCode = 404,
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ];
        }
    }

    public function coreValidateParams($dataProvider, $searchModel = null): array|bool
    {
        if (isset($dataProvider->errors)) {
            return $this->coreError($dataProvider, 'validationFailed');
        }

        if ($searchModel !== null && !$searchModel->validate()) {
            return $this->coreError($searchModel, 'badRequest');
        }

        return false;
    }

    public function coreEmptyParams($model): bool|array
    {
        $scenario = $model->scenario;
		$message = null;

        if (empty($model->getDirtyAttributes()) && $scenario === Constants::SCENARIO_UPDATE) {
            $message = 'noRecordUpdated';
        }

        if ($scenario === Constants::SCENARIO_DELETE && $model->status === $model->getOldAttribute('status')) {
            $message = 'noRecordDeleted';
        }

		if ($message){
			return [
				'code' => Yii::$app->response->statusCode = 422,
				'success' => false,
				'message' => Yii::t('core', $message),
				'errors' => $model->errors ?? [],
			];
		}

		return false;
    }

    public function coreSuperadmin(?array $params): array|bool
    {
        $status = (int)($params['status'] ?? null);
        $restrictStatus = Constants::restrictStatus();

        if (
            !$this->superAdmin()
            && $status !== null
            && in_array($status, $restrictStatus, true)
        ) {
            return [
                'code' => Yii::$app->response->statusCode = 403,
                'success' => false,
                'message' => Yii::t('core', 'superadminOnly'),
                'errors' => [],
            ];
        }

        return false;
    }

    public function superAdmin(): bool
    {
        $roles = Yii::$app->session->get('roles', []);

        return !in_array('superadmin', $roles, true);
    }
}
