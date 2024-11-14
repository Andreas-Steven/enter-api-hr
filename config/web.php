<?php

$params = require __DIR__ . '/params.php';
$db = require __DIR__ . '/db.php';
// $mongodb = require __DIR__ . '/mongodb.php';

$config = [
    'id' => 'basic'.$params['extraCookies'],
    'name' => $params['titleService'],
    'timeZone' => $params['timeZone'],
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'language' => $params['defaultLanguage'],
    'aliases' => [
        '@bower' => '@vendor/bower-asset',
        '@npm'   => '@vendor/npm-asset',
    ],
    'components' => [
        'pagination' => [
            'class' => 'yii\data\Pagination',
            'defaultPageSize' => 10,
        ],
        'coreAPI' => [
            'class' => 'app\components\CoreAPI',
        ],
        'request' => [
            // !!! insert a secret key in the following (if it is empty) - this is required by cookie validation
            // 'cookieValidationKey' => 'rHaRkLSi4OMU-_Gi8LBn0Fh8IcqrYJP7'.$params['extraCookies'],
            'enableCookieValidation' => false,
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
            'enableSession' => false,
            'loginUrl' => null
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'response' => [
            'class' => 'yii\web\Response',
            'on beforeSend' => function ($event) {
                $response = $event->sender;
                // if ($response->data !== null && Yii::$app->request->get('suppress_response_code')) {
                //     $response->data = [
                //         'success' => $response->isSuccessful,
                //         'data' => $response->data,
                //     ];
                //     $response->statusCode = 200;
                // } else {
                //     $response->data['success'] = $response->isSuccessful;
                //     unset($response->data['type']);
                // }
            },
        ],
        'mailer' => [
            'class' => \yii\symfonymailer\Mailer::class,
            'viewPath' => '@app/mail',
            // send all mails to a file by default.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    // adding for query logging
                    'categories' => ['yii\db\Command::execute'],
                    'logFile' => '@runtime/logs/sql.log',  // Log SQL queries to a specific file
                    'maxFileSize' => 1024 * 2,
                    'maxLogFiles' => 10,
                    'logVars' => [],  // Prevents logging of $_SERVER, $_SESSION, etc.
                ],
            ],
        ],
        'db' => $db,
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                '/' => 'site/index',
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => [
                        'v1/default',
                    ]
                ],
                'v1' => 'v1/default/index',
                'v1/data' => 'v1/default/data',
                // 'v1/category/searchLastPosition/<id>' => 'v1/category/hierarchy',
                // 'v1/brand' => 'v1/brand/index',
                // 'v1/brand/data' => 'v1/brand/data',
            ],
        ],
        'i18n' => [
            'translations' => [
                'app' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/translation',
                    'fileMap' => [
                        'app' => 'app.php',
                    ],
                    'on missingTranslation' => ['app\components\TranslationEventHandler', 'handleMissingTranslation']
                ],
                'rule' => [
                    'class' => 'yii\i18n\PhpMessageSource',
                    'basePath' => '@app/translation',
                    'fileMap' => [
                        'rule' => 'rule.php',
                    ],
                    'on missingTranslation' => ['app\components\TranslationEventHandler', 'handleMissingTranslation']
                ]
            ],
        ],
    ],
    'params' => $params,
    'modules' => [
        'v1' => [
            'class' => 'app\modules\v1\Module',
        ],
    ],
    'on beforeAction' => function ($event ) use ($params) {
        $req = Yii::$app->request->getBodyParams();
        if (isset($req['language'])) {
            Yii::$app->language = $req['language'];
            if(!in_array(Yii::$app->language, $params['languages'])) {
                Yii::$app->language = $params['defaultLanguage'];
            }
        }
    },
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = [
        'class' => 'yii\debug\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = [
        'class' => 'yii\gii\Module',
        // uncomment the following to add your IP if you are not connecting from localhost.
        //'allowedIPs' => ['127.0.0.1', '::1'],
    ];
}

return $config;
