<?php

return [
    'titleService' => 'enterkomputer-human-resource',
    'serviceVersion' => 'V1',
    'timeZone' => 'Asia/Jakarta',
    'extraCookies' => 'name-cookie-01',
    // DONT FORGET TO CHANGE THIS WHEN IN PRODUCTION
    'codeApp' => 'enterHR',
    'defaultLanguage' => 'en',
    'UTC' => 'Y-m-d\TH:i:s\Z',
    'localTimestamp' => 'Y-m-d H:i:s',
    'languages' => [
        'en', 
        'id'
    ],
    'jwt' => [
        'key' => 'secret-key-harus-panjang-256-bit',
        'algorithm' => 'HS256',
        'expire' => '+1 hour',
        'issuer' => 'https://sso.dev-enterkomputer.com',
        'audience' => 'https://sso.dev-enterkomputer.com',
        'id' => 'sso-auth-v1',
        'request_time' => '+5 seconds',
        // DONT FORGET TO CHANGE THIS WHEN IN PRODUCTION
        'except' => YII_ENV_DEV ? ['*'] : ['index'],
    ],
    'request' => [
        // DONT FORGET TO CHANGE THIS WHEN IN PRODUCTION
        // 'enableCsrfValidation' => !YII_ENV_DEV,
        'enableCsrfValidation' => false,
    ],
    'cors' => [
        'requestMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
        'origins' => ['http://localhost:5173', 'https://entersys.dev-enterkomputer.com'],
        'allowHeaders' => ['Origin','Content-Type','Authorization','Accept-Language'],
    ],
    'pagination' => [
        'pageSize' => 10,
        'sortDir' => SORT_DESC,
    ],
    'verbsAction' => [
        'index' => ['get'],
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
];
