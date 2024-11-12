<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'pgsql:host=localhost;port=5432;dbname=dev_master_data',
    'username' => 'dev_ironhead',
    'password' => 'vYbrXeVMy#X7',
    'charset' => 'utf8',
    'enableLogging' => true,
    'enableProfiling' => true,

    // Schema cache options (for production environment)
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',
];