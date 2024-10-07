<?php

namespace app\controllers;

use Yii;
use yii\rest\Controller;
use yii\web\Response;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'contentNegotiator' => [
                'class' => 'yii\filters\ContentNegotiator',
                'formats' => [
                    'application/json' => Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actionIndex()
    {
        \Yii::$app->language = 'id'; // set language output
        $data = [
            'code' => 200,
            'success' => true,
            'message' => Yii::$app->params['titleService'],
            'languages' => Yii::t('app', 'responseLanguage'),
        ];

        return $data;
    }

    public function actionError()
    {
        $data = [
            'status' => Yii::$app->errorHandler->exception->statusCode,
            'success' => false,
            'message' => Yii::$app->errorHandler->exception->getMessage(),
        ];

        return $data;
    }
   
}
