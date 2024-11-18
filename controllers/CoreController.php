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
		$this->enableCsrfValidation = Constants::ENABLE_CSRF_VALIDATION;
        
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
                    'Origin' => ['http://localhost:5173'],
                    'Access-Control-Request-Headers' => ['*'],
                    'Access-Control-Allow-Credentials' => true,
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'DELETE'],
                    'Access-Control-Allow-Headers' => ['Origin','Content-Type','Authorization'],
                ],
            ],
        ];
    }

    public function coreActionIndex()
	{
		$data = [
			'code' => Yii::$app->response->statusCode = 200,
			'success' => true,
			'message' => Yii::$app->params['titleService'].' '.Yii::$app->params['serviceVersion'],
		];

		return $data;
	}

    public function coreValidateParams($dataProvider, $searchModel=null) 
    {
        if (isset($dataProvider->error)){
            $data = [
                'code' => Yii::$app->response->statusCode = 422,
                'success' => false,
                'message' => Yii::t('rule', 'fieldValidationFailed'),
                'errors' => $dataProvider->params,
            ];
    
            return $data;
        }

        if (isset($searchModel)){
            if (!$searchModel->validate()) {
                $data = [
                    'code' => Yii::$app->response->statusCode = 422,
                    'success' => false,
                    'message' => Yii::t('rule', 'badRequest'),
                    'errors' => $searchModel->errors,
                ];
        
                return $data;
            }
        }

        return false;
    }

    public function coreValidationFailed($model, $mesage) 
    {
        $data = [
            'code' => Yii::$app->response->statusCode = 422,
            'success' => false,
            'message' => Yii::t('rule', $mesage),
            'errors' => $model,
        ];

        return $data;
    }

    public function coreSuccess($model, $message) 
    {
        $data = [
            'code' => Yii::$app->response->statusCode = 200,
            'success' => true,
            'message' => Yii::t('rule', $message),
            'data' => $model,
        ];

        return $data;
    }

    public function coreData($dataProvider) 
    {
        $total = $dataProvider->count > 0 ? $dataProvider->totalCount : 0;

        $data = [
			'code' => Yii::$app->response->statusCode = 200,
			'success' => true,
			'message' => Yii::t('app', 'success'),
			'pagination' => [
				'page' => $dataProvider->pagination->page + 1,
				'totalCount' => $dataProvider->totalCount,
				'total' => $total,
				'display' => $dataProvider->count,
			],
			'data' => $dataProvider,
		];

        return $data;
    }

    public function coreList($mongodb) 
    {
        $data = [
			'code' => Yii::$app->response->statusCode = 200,
			'success' => true,
			'message' => Yii::t('app', 'success'),
			'pagination' => [
				// 'page' => $mongodb->pagination->page + 1,
				// 'total' => $mongodb->totalCount,
				// 'display' => $mongodb->count,
			],
			'data' => $mongodb,
		];

        return $data;
    }

    public function coreSuperadmin($params) 
    {
        if (isset($params['status'])) {
            $roles = Yii::$app->session->get('roles', []);
            $status = (int) $params['status'];
            $restrictStatus = Constants::restrictStatus();

            if (!Yii::$app->coreAPI->superAdmin() && in_array($status, $restrictStatus)) {
                $data = [
                    'code' => Yii::$app->response->statusCode = 403,
                    'success' => false,
                    'message' => Yii::t('app', 'superadminOnly'),
                    'errors' => [],
                ];

                return $data;
            }
        }

        return false;
    }

    public function coreBadRequest($params, $scenario) 
    {
        $error = [];

        if (isset($params['id'])) {
            $id = $params['id'];
            unset($params['id']);

            if ($scenario == Constants::SCENARIO_UPDATE && empty($params)) {
                $error = [
                    'message' => Yii::t('rule', 'badRequest'),
                    'error' => Yii::t('rule', 'paramsEmpty'),
                ];
            }

            if (!is_numeric($id) || intval($id) != $id) {
                $error = [
                    'message' => Yii::t('rule', 'badRequest'),
                    'error' => Yii::t('rule', 'idMustInteger'),
                ];
            } 
        } else {
            $error = [
                'message' => Yii::t('rule', 'badRequest'),
                'error' => Yii::t('rule', 'idCannotBlank'),
            ];
        }

        if (!empty($error)) {
            return [
                'code' => Yii::$app->response->statusCode = 400,
                'success' => false,
                'message' => $error['message'],
                'errors' => [['field' => 'id', 'message' => $error['error']]],
            ];
        }

        return $error;
    }

    
    public function coreBadRequest__($model, $mesage) 
    {
        Yii::$app->response->statusCode = 400;
        
        $data = [
            'code' => 400,
            'success' => false,
            'message' => $mesage,
            'errors' => $model,
        ];

        return $data;
    }

    public function coreDataNotFound()
    {
        try {
            throw new CustomException(Yii::t('rule', 'dataNotFound'));
        } catch (CustomException $e) {
            $data = [
                'code' => Yii::$app->response->statusCode = 400,
                'success' => false,
                'message' => $e->getMessage(),
                'errors' => [],
            ];
        }

        return $data;
    }

    public function coreUniqueData($model, $field, $params) 
    {
        if (isset($params[$field])){
            $paddedCode = str_pad(strtoupper($params[$field]), Constants::CODE_MAX_LENGTH, Constants::CODE_EXCEPTION, STR_PAD_RIGHT);
		    $existingCode = $model::findOne(['code' => $paddedCode]);

            if ($existingCode !== null) {
                $modelInstance = new $model();
                $label = $modelInstance->attributeLabels()[$field];
                
                $data = [
                    'code' => Yii::$app->response->statusCode = 400,
                    'success' => false,
                    'message' => Yii::t('rule', 'valueExists'),
                    'errors' => [['field' => $label, 'message' => Yii::t('rule', 'valueAlreadyExists', ['field' => $label])]],
                ];
    
                return $data;
            }
        }

        return false;
    }
}
