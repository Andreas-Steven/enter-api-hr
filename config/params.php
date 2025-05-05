<?php

/*
 * Parameters configuration for the application.
 * 
 * Version: 1.0.0
 * Version Date: 2025-05-05
 */

$params_json = require __DIR__ . '/params_json.php';

return array_merge($params_json, [
    'titleService' => 'enterkomputer-human-resource',
    'serviceVersion' => 'V1',
    #DONT FORGET TO CHANGE THIS WHEN IN PRODUCTION
    'extraCookies' => 'name-cookie-01',
    'codeApp' => 'enterHR',
    'migrateFresh' => YII_ENV_DEV,
    'timestamp' => [
        'timeZone' => 'Asia/Jakarta',
        'UTC' => 'Y-m-d\TH:i:s\Z',
        'local' => 'Y-m-d H:i:s'
    ],
    'language' => [
        'default' => 'en',
        'list' => [
            'en', 
            'id',
        ],
    ],
    'jwt' => [
        'key' => 'secret-key-harus-panjang-256-bit',
        'algorithm' => 'HS256',
        'expire' => '+1 hour',
        'issuer' => 'https://sso.dev-enterkomputer.com',
        'audience' => 'https://sso.dev-enterkomputer.com',
        'id' => 'sso-auth-v1',
        'request_time' => '+5 seconds',
        #DONT FORGET TO CHANGE THIS WHEN IN PRODUCTION
        'except' => YII_ENV_DEV ? ['*'] : ['index'],
    ],
    'request' => [
        #DONT FORGET TO CHANGE THIS WHEN IN PRODUCTION
        // 'enableCsrfValidation' => !YII_ENV_DEV,
        'enableCsrfValidation' => false,
    ],
    'cors' => [
        'requestMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'origins' => ['http://localhost:5173', 'https://entersys.dev-enterkomputer.com'],
        'allowHeaders' => ['Origin','Content-Type','Authorization','Accept-Language'],
    ],
    'pagination' => [
        'pageSize' => 10,
        'sortDir' => SORT_DESC,
    ],
    'verbsAction' => [
        'index' => ['get'],
        'data' => ['post'],
        'list' => ['post'],
        'create' => ['post'],
        'update' => ['put'],
        'delete' => ['delete'],
        'view' => ['post'],
    ],
    'mailer' => [
        'adminEmail' => 'admin@example.com',
        'senderEmail' => 'noreply@example.com',
        'senderName' => 'Example.com mailer',
    ],
]);
