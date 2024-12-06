<?php

namespace app\components;

use Yii;
use yii\web\ErrorHandler;
use yii\web\Response;
use yii\web\HttpException;
use yii\web\NotFoundHttpException;

class CustomErrorHandler extends ErrorHandler
{
    protected function renderException($exception)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $statusCode = $exception instanceof HttpException ? $exception->statusCode : 500;

        if ($exception !== null) {
            $response = [
                'code' => $statusCode,
                'success' => false,
                'message' => $exception->getMessage(),
                'errors' => [],
            ];

            if ($exception instanceof NotFoundHttpException) {
                $response['message'] = 'The requested resource was not found.';
            }

            Yii::$app->response->data = $response;
            Yii::$app->response->statusCode = $statusCode;
        } else {
            Yii::$app->response->data = [
                'code' => 500,
                'success' => false,
                'message' => 'An unknown error occurred.',
                'errors' => [],
            ];
            Yii::$app->response->statusCode = 500;
        }

        Yii::$app->response->send();
    }
}