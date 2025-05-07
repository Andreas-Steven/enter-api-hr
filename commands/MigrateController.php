<?php

namespace app\commands;

use yii\console\ExitCode;
use yii\console\controllers\MigrateController as BaseMigrateController;

class MigrateController extends BaseMigrateController
{
    public $migrationPath = [
        '@app/migrations',
    ];

    public function beforeAction($action)
    {
        $params = require(\Yii::getAlias('@app/config/params.php'));

        if ($params['migrateFresh'] === false && $action->id === 'fresh') {
            echo "Skipping the migrate/fresh command in non-dev environment.\n";
            return ExitCode::OK;
        }

        return parent::beforeAction($action);
    }

    public function actionFresh()
    {
        if ($this->confirm('Are you sure you want to drop all tables and re-run all migrations? This will erase all data.')) {
            $this->actionDown('all');
            return $this->actionUp();
        }
        return ExitCode::OK;
    }
}