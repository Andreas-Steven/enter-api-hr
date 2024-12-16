<?php

namespace app\core;

use Yii;
use yii\rest\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\components\CustomException;
use app\core\Constants;

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

    public function corecoreFindModel($model, ?array $params): ?object
    {
        $where = $params;
        $id = isset($params['id']) ? $params['id'] : null;

        if ($id){
            $where = ['id' => $id];
        }

        if (!empty($where)) {
            $query = $model::find()->where($where);
            if (($modelInstance = $query->one()) !== null) {
                return $modelInstance;
            }
        }

        return null;
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

    public function coreError($model, ?string $message = null): array 
    {
        return [
            'code' => Yii::$app->response->statusCode = 422,
            'success' => false,
            'message' => Yii::t('core', $message ?? "{$model->scenario}RecordFailed"),
            'errors' => isset($model->errors) ? $model : [],
        ];
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

    public function coreBadRequest($model, ?string $message = null): array
    {
        return [
            'code' => Yii::$app->response->statusCode = 400,
            'success' => false,
            'message' => $message ?? Yii::t('core', 'badRequest'),
            'errors' => $model ?? [],
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

    public function coreValidateProvider($dataProvider, $searchModel = null): array|bool
    {
        if (isset($dataProvider->errors)) {
            return $this->coreError($dataProvider, 'validationFailed');
        }

        if ($searchModel !== null && !$searchModel->validate()) {
            return $this->coreError($searchModel, 'badRequest');
        }

        return false;
    }

    public function coreValidateParams(?array $params, string $scenario = 'default'): array|bool
    {
        $error = [];
        $idKey = 'id';

        (!isset($params[$idKey])) ? $error[] = [
            'field' => $idKey,
            'message' => Yii::t('core', 'required', ['field' => $idKey]),
        ] : null;

        if (isset($params[$idKey])) {
            $id = $params[$idKey];
            unset($params[$idKey]);

            (!is_numeric($id) || intval($id) != $id) ? $error[] = [
                'field' => $idKey,
                'message' => Yii::t('core', 'integer', ['field' => $idKey]),
            ] : null;

            ($scenario === Constants::SCENARIO_UPDATE && empty($params)) ? $error[] = [
                'field' => $idKey,
                'message' => Yii::t('core', 'emptyParams'),
            ] : null;
        }

        if (!empty($error)) {
            return [
                'code' => Yii::$app->response->statusCode = 400,
                'success' => false,
                'message' => Yii::t('core', 'invalidField', ['field' => $idKey]),
                'errors' => $error,
            ];
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

    public function coreUnavailableParams($model, ?array $params): array|bool
	{
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($model, $params)) {
            return [
                'code' => Yii::$app->response->statusCode = 422,
                'success' => false,
                'message' => Yii::t('core', 'validationFailed'),
                'errors' => $unavailableParams ?? [],
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
