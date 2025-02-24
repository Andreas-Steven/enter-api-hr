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

        if (!YII_ENV_DEV) {
            echo "Skipping migration/fresh for non-dev environment.\n";
            return true;
        }
        
        $this->createTable('{{%' . $this->tableName . '}}', [
            'id' => $this->string(20),
            'province' => $this->string(150),
            'district' => $this->string(150),
            'sub_district' => $this->string(150),
            'type' => $this->string(15),
            'city' => $this->string(150),
            'zip_code' => $this->string(10),
        ]);

        $this->addPrimaryKey('pk-id', '{{%shipment_destination}}', ['id']);

        if (!YII_ENV_DEV) {
            echo "Skipping migration/fresh for non-dev environment.\n";
            return true;
        }

        // $dummyData = json_decode(file_get_contents(\Yii::getAlias('@app/migrations/shipment_destination.json')), true);
        // $mongodb = new Client($mongodb_param['dsn']);
        // $collection = $mongodb->selectCollection($mongodb_param['database'], $this->tableName);
        // $collection->drop();

        // foreach ($dummyData as $key => $value) {
        //     $value['address'] = $value['sub_district'] . ', Kec. ' . $value['district']. ', ' . $value['type'] . ' ' . $value['city'] . ', ' . $value['province'] . ' ' . $value['zip_code'];
        //     $collection->insertOne($value);
        // }
    }

    public function safeDown()
    {
        if (!YII_ENV_DEV) {
            echo "Skipping migration/fresh for non-dev environment.\n";
            return true;
        }

        $this->dropTable('{{%' . $this->tableName . '}}');

        // $mongodb_param = require(__DIR__ . '/../config/mongodb.php');
        // $mongodb = new Client($mongodb_param['dsn']);
        // $collection = $mongodb->selectCollection($mongodb_param['database'], $this->tableName);
        // $collection->drop();
    }
}
