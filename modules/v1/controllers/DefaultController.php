<?php

namespace app\modules\v1\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;

/**
 * Default controller for the `v1` module
 */
class DefaultController extends Controller
{
    public $enableCsrfValidation = false;

    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
            'authenticator' => [
                'class' => 'app\components\JwtBearerAuth',
                'except' => [
                    'index',
                ],
            ],
        ];
    }

    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        $data = [
            'code' => 200,
            'success' => true,
            'message' => Yii::$app->params['titleService'].' v1',  
        ];

        return $data;
    }

    public function actionData()
    {
        $data = [
            'code' => 200,
            'success' => true,
            'message' => Yii::t('app', 'ok') . ' - ' . Yii::$app->params['titleService'], 
            'username' => Yii::$app->session->get('username'),
            'userid' => Yii::$app->session->get('userid'),
            'role' => Yii::$app->session->get('role'), 
        ];

        return $data;
    }

}
