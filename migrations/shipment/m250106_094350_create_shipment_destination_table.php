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
        $params = require(\Yii::getAlias('@app/config/params.php'));
        $mongodb_param = require(\Yii::getAlias('@app/config/mongodb.php'));
        $UTCTimestamp = gmdate($params['timestamp']['UTC']);
        $dbDefault = $params['dbDefault'];
        
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

        $dummyData = []; #json_decode(file_get_contents(\Yii::getAlias('@app/migrations/shipment_destination.json')), true);

        #uncomment below code if you want to insert data to mongodb
        // $mongodb = new Client($mongodb_param['dsn']);
        // $collection = $mongodb->selectCollection($mongodb_param['database'], $this->tableName);
        // $collection->drop();

        foreach ($dummyData as $key => $value) {
            $this->insert('{{%' . $this->tableName . '}}', $value);

            #uncomment below code if you want to insert data to mongodb
            // $insertID['id'] = $key + 1;
            // $collection->insertOne(array_merge($insertID, $value));

            #shipment insert data
            // $value['address'] = $value['sub_district'] . ', Kec. ' . $value['district']. ', ' . $value['type'] . ' ' . $value['city'] . ', ' . $value['province'] . ' ' . $value['zip_code'];
            // $collection->insertOne($value);
        }
    }

    public function safeDown()
    {
        $params = require(\Yii::getAlias('@app/config/params.php'));
        $mongodb_param = require(\Yii::getAlias('@app/config/mongodb.php'));
        $dbDefault = $params['dbDefault'];
        
        if (!YII_ENV_DEV) {
            echo $dbDefault['skipMigrateFresh'];
            return true;
        }

        #uncomment below code if you want to insert data to mongodb
        // $mongodb = new Client($mongodb_param['dsn']);
        // $collection = $mongodb->selectCollection($mongodb_param['database'], $this->tableName);
        // $collection->drop();

        $this->dropTable('{{%' . $this->tableName . '}}');
    }
}
