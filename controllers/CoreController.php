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
    public function behaviors()
    {
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


    public function coreValidateParams($dataProvider) 
    {
        if (isset($dataProvider->error)){
            Yii::$app->response->statusCode = 422;
            
            $data = [
                'code' => 422,
                'success' => false,
                'message' => Yii::t('app', 'fieldValidationFailed'),
                'errors' => $dataProvider->params,
            ];
    
            return $data;
        }

        return false;
    }

    public function coreBadRequest($model, $mesage) 
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

    public function coreSuccess($model, $message) 
    {
        Yii::$app->response->statusCode = 200;
        $data = [
            'code' => 200,
            'success' => true,
            'message' => Yii::t('app', $message),
            'data' => $model,
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
                Yii::$app->response->statusCode = 403;
                
                $data = [
                    'code' => 403,
                    'success' => false,
                    'message' => Yii::t('app', 'superadminOnly'),
                    'errors' => [],
                ];

                return $data;
            }
        }

        return false;
    }

    public function coreBadRequestNoParams($params, $scenario) 
    {
        if (isset($params['id'])) {
            $id = $params['id'];
            unset($params['id']);

            if (!is_numeric($id) || intval($id) != $id) {
                return [
                    'code' => 400,
                    'success' => false,
                    'message' => Yii::t('app', 'badRequest'),
                    'errors' => [['field' => 'id', 'message' => 'id must be an integer.']],
                ];
			}
            
            if ($scenario == Constants::SCENARIO_UPDATE && sizeof($params) == 0) {
                Yii::$app->response->statusCode = 400;
            
                return [
                    'code' => 400,
                    'success' => false,
                    'message' => Yii::t('app', 'paramsEmpty'),
                    'errors' => [],
                ];
            }
        } else {
            return [
                'code' => 400,
                'success' => false,
                'message' => Yii::t('app', 'badRequest'),
                'errors' => [['field' => 'id', 'message' => 'id cannot be blank.']],
            ];
        }

        return false;
    }

    public function coreDataNotFound()
    {
        Yii::$app->response->statusCode = 400;

        $data = [
            'code' => 400,
            'success' => false,
            'message' => 'Data Not Found',
            'errors' => [],
        ];

        return $data;

        // try {
        //     throw new CustomException(Yii::t('app', 'dataNotFound'));
        // } catch (CustomException $e) {
        //     $data = [
        //         'code' => 400,
        //         'success' => false,
        //         'message' => $e->getMessage(),
        //         'errors' => [],
        //     ];
        // }

        // return $data;
    }

    public function coreUniqueData($model, $field, $params) 
    {
        if (isset($params[$field])){
            $paddedCode = str_pad(strtoupper($params[$field]), Constants::CODE_MAX_LENGTH, Constants::CODE_EXCEPTION, STR_PAD_RIGHT);
		    $existingCode = $model::findOne(['code' => $paddedCode]);

            if ($existingCode !== null) {
                Yii::$app->response->statusCode = 400;
                $modelInstance = new $model();
                $label = $modelInstance->attributeLabels()[$field];
                
                $data = [
                    'code' => 400,
                    'success' => false,
                    'message' => Yii::t('app', 'valueExists'),
                    'errors' => [['field' => $label, 'message' => Yii::t('app', 'valueAlreadyExists', ['field' => $label])]],
                ];
    
                return $data;
            }
        }

        return false;
    }
}
