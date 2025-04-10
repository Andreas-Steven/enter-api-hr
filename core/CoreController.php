<?php

namespace app\core;

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
            'contentNegotiator' => [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'authenticator' => [
                'class' => 'app\components\JwtBearerAuth',
                'except' => Yii::$app->params['jwt']['except'],
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
		self::actionIndex();
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

    public function coreFindModelOne($model, ?array $paramsID, ?array $otherParams = []): ?object
	{
        $id = $paramsID['id'] ?? null;
        $where = [];

        if ($id) {
            $where = ['id' => $id];
        }

        if ($otherParams) {
            $where = array_merge($where, $otherParams);
        }

		if (!empty($where)) {
			$query = $model::find()->where($where);
			if (($modelInstance = $query->one()) !== null) {
                $lockVersion = Constants::OPTIMISTIC_LOCK;

                if (isset($modelInstance->$lockVersion)) {
                    // Hide lock_version on result data for update/delete.
                    unset($modelInstance->$lockVersion);
                }

				return $modelInstance;
			}
		}

		return null;
	}

    public function coreData($dataProvider): array
    {
        return [
            'code' => Yii::$app->response->statusCode = 200,
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

    public function coreCustomData($model=[], ?string $message = null): array
    {
        $response = $model ?? [];
        $message = $message ?? Yii::t('app', "{$model->scenario}RecordSuccess");

        return [
            'code' => Yii::$app->response->statusCode = 200,
            'success' => true,
            'message' => $message,
            'data' => $response,
        ];
    }

    public function coreSuccess($model=[], ?string $message = null, ?array $data=null): array
    {
        $response[] = $model ?? [];
        $message = $message ?? Yii::t('app', "{$model->scenario}RecordSuccess");

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
            'message' => $message ?? Yii::t('app', "{$model->scenario}RecordFailed"),
            'errors' => isset($model->errors) ? $model : [],
        ];
    }

    public function coreDataNotFound(): array
    {
        try {
            throw new \Exception(Yii::t('app', 'dataNotFound'));
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
            'message' => $message ?? Yii::t('app', 'badRequest'),
            'errors' => $model ?? [],
        ];
    }

    public function coreValidateProvider($dataProvider, $searchModel = null): array|bool
    {
        if (isset($dataProvider->errors)) {
            return $this->coreError($dataProvider, Yii::t('app', 'validationFailed'));
        }

        if ($searchModel !== null && !$searchModel->validate()) {
            return $this->coreError($searchModel, Yii::t('app', 'badRequest'));
        }

        return false;
    }

    public function coreValidateParams(?array $params, string $scenario = 'default'): array|bool
    {
        $error = [];
        $idKey = 'id';
        $messageError = null;

        if (isset($params[$idKey])) {
            $id = $params[$idKey];
            unset($params[$idKey]);

            (!is_numeric($id) || intval($id) != $id) ? $error[] = [
                'field' => $idKey,
                'message' => Yii::t('app', 'integer', ['label' => $idKey]),
            ] : null;

            ($scenario === Constants::SCENARIO_UPDATE && empty($params)) ? $error[] = [
                'field' => $idKey,
                'message' => Yii::t('app', 'emptyParams'),
            ] : null;
        } else {
            $messageError = Yii::t('app', 'validationFailed');

            $error[] = [
                'field' => $idKey,
                'message' => Yii::t('app', 'required', ['label' => $idKey]),
            ];
        }

        if (!empty($error)) {
            return [
                'code' => Yii::$app->response->statusCode = 400,
                'success' => false,
                'message' => $messageError ?? Yii::t('app', 'invalidField', ['label' => $idKey]),
                'errors' => $error,
            ];
        }

        return false;
    }

    public function coreEmptyParams($model, $scenario = null): bool|array
    {
		$message = null;
        $scenario = $scenario ?? Constants::SCENARIO_UPDATE;
        $optimisticLock = Constants::OPTIMISTIC_LOCK;

        $getDirtyAttributes = $model->getDirtyAttributes();
        unset($getDirtyAttributes['id']);
        unset($getDirtyAttributes[$optimisticLock]);

        if (empty($getDirtyAttributes) && in_array($scenario, [Constants::SCENARIO_UPDATE, $scenario], true)) {
            $message = Yii::t('app', 'noRecordUpdated');
        }

        if ($scenario === Constants::SCENARIO_DELETE && $model->status === $model->getOldAttribute('status')) {
            $message = Yii::t('app', 'noRecordDeleted');
        }

        return $message ? [
            'code' => Yii::$app->response->statusCode = 422,
            'success' => false,
            'message' => $message,
            'errors' => $model->errors ?? [],
        ] : false;
    }

    public function coreUnavailableParams($model, ?array $params): array|bool
	{
        if ($unavailableParams = Yii::$app->coreAPI->unavailableParams($model, $params)) {
            return [
                'code' => Yii::$app->response->statusCode = 422,
                'success' => false,
                'message' => Yii::t('app', 'validationFailed'),
                'errors' => $unavailableParams ?? [],
            ]; 
        }

        return false;
	}

    public function coreSuperadmin(?array $params): array|bool
    {
        $status = (int)($params['status'] ?? null);
        $restrictStatus = Constants::RESTRICT_STATUS_LIST;

        if (
            !$this->superAdmin()
            && $status !== null
            && in_array($status, $restrictStatus, true)
        ) {
            return [
                'code' => Yii::$app->response->statusCode = 403,
                'success' => false,
                'message' => Yii::t('app', 'superadminOnly'),
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
