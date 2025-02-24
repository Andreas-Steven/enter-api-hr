<?php

use yii\db\Migration;
use MongoDB\Client;

/**
 * Handles the creation of table `{{%shipment_destination}}`.
 */
class m250106_094350_create_shipment_destination_table extends Migration
{
    private $tableName = 'shipment_destination';
    
    public function safeUp()
    {
        $mongodb_param = require(__DIR__ . '/../config/mongodb.php');
        $params = require(__DIR__ . '/../config/params.php');
        $UTCTimestamp = gmdate($params['timestamp']['UTC']);
        $createdBy = 'system';

        $dummyData = json_decode(file_get_contents(\Yii::getAlias('@app/migrations/shipment_destination.json')), true);
        
        if (!YII_ENV_DEV) {
            echo "Skipping migration/fresh for non-dev environment.\n";
            return true;
        }
        
        $mongodb = new Client($mongodb_param['dsn']);
        $collection = $mongodb->selectCollection($mongodb_param['database'], $this->tableName);
        $collection->drop();

        if (!YII_ENV_DEV) {
            echo "Skipping migration/fresh for non-dev environment.\n";
            return true;
        }

        $collection->createIndex([
            'code' => 'text',
            'province' => 'text',
            'district' => 'text',
            'sub_district' => 'text',
            'city' => 'text',
            'zip_code' => 'text',
        ]);

        foreach ($dummyData as $key => $value) {
            $collection->insertOne($value);
        }
    }

    public function safeDown()
    {
        if (!YII_ENV_DEV) {
            echo "Skipping migration/fresh for non-dev environment.\n";
            return true;
        }

        $mongodb_param = require(__DIR__ . '/../config/mongodb.php');
        
        $mongodb = new Client($mongodb_param['dsn']);
        $collection = $mongodb->selectCollection($mongodb_param['database'], $this->tableName);
        $collection->drop();
    }
}
