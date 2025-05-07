<?php

use yii\db\Migration;

/**
 * Handles adding the inventory_inbound function to the inventory table.
 */
class m241010_103834_create_inventory_inbound_function extends Migration
{
    public function safeUp()
    {
        $params = require(\Yii::getAlias('@app/config/params.php'));
        $dbDefault = $params['dbDefault'];
        
        if (!YII_ENV_DEV) {
            echo $dbDefault['skipMigrateFresh'];
            return true;
        }
        // Example used of function SELECT code, new_quantity, old_quantity, md_data, message, payload FROM inventory_inbound(1, 1, '1',  ARRAY[1021, 1022, 1023, 1024, 1025, 1026, 1027, 1028, 1029, 1030, 1033]);

        $this->execute("
            CREATE OR REPLACE FUNCTION inventory_inbound(
                inventory_id INT,
                status_id INT,
                product_id TEXT,
                new_md_data INTEGER[]
            ) RETURNS TABLE(
                code INTEGER, 
                new_quantity INTEGER,
                old_quantity INTEGER,
                message TEXT, 
                payload JSON,
                md_data JSONB
            ) AS $$
            DECLARE
                old_quantity INT;
                new_quantity INT;
                current_md_data INTEGER[];
                updated_md_data INTEGER[];
                product_detail JSONB;
                payload JSON := '[]'::json;
            BEGIN
                -- Construct the payload
                payload := jsonb_build_object(
                    'inventory_id', inventory_id,
                    'product_id', product_id,
                    'status', status_id
                )::json;

                -- Retrieve the current md_data
                SELECT array_agg(DISTINCT elem::INT ORDER BY elem::INT) INTO current_md_data
                FROM inventory, jsonb_array_elements_text(detail_item->product_id->'md_data') AS elem
                WHERE id = inventory_id
                AND status = status_id;

                -- If current_md_data is NULL, return with code 400
                IF current_md_data IS NULL THEN
                    SELECT detail_item->product_id INTO product_detail
                    FROM inventory
                    WHERE id = inventory_id
                    AND status = status_id;
                    RAISE NOTICE 'Updated product_detail: %', product_detail;

                    IF product_detail IS NULL THEN
                        RETURN QUERY SELECT 
                            400 AS code, 
                            NULL::INTEGER AS new_quantity, 
                            NULL::INTEGER AS old_quantity,
                            'Product in the inventory is not found or empty data.' AS message, 
                            payload AS payload,
                            NULL::JSONB AS md_data;
                        RETURN;
                    END IF;
                END IF;

                -- Retrieve the old quantity
                old_quantity := COALESCE(array_length(current_md_data, 1), 0);

                -- Combine current md_data with new_md_data, remove duplicates, and sort the array in ascending order
                SELECT array_agg(DISTINCT elem ORDER BY elem) INTO updated_md_data
                FROM (
                    SELECT unnest(current_md_data) AS elem UNION 
                    SELECT unnest(new_md_data) AS elem
                ) AS combined_data;
                
                -- Update the new size of md_data in quantity
                new_quantity := COALESCE(array_length(updated_md_data, 1), 0);

                -- If the data is the same, return with code 400
                IF updated_md_data IS NOT DISTINCT FROM current_md_data THEN
                    RETURN QUERY SELECT 
                        400 AS code, 
                        new_quantity, 
                        old_quantity,
                        'Inventory not updated, all product already in this inventory.' AS message, 
                        payload AS payload,
                        to_jsonb(new_md_data) AS md_data;
                    RETURN;
                END IF;

                -- Update the inventory table with the new md_data and quantity
                UPDATE inventory
                SET detail_item = jsonb_set(
                    jsonb_set(detail_item, ARRAY[product_id, 'md_data'], to_jsonb(updated_md_data)),
                    ARRAY[product_id, 'quantity'],
                    to_jsonb(new_quantity)
                )
                WHERE id = inventory_id;

                RAISE NOTICE 'Updated md_data: %', updated_md_data;
                RAISE NOTICE 'Updated new_quantity: %', new_quantity;
                RAISE NOTICE 'Old quantity: %', old_quantity;

                RETURN QUERY SELECT 
                    200 AS code, 
                    new_quantity, 
                    old_quantity,
                    NULL AS message, 
                    payload AS payload,
                    to_jsonb(new_md_data) AS md_data;
            END;
            $$ LANGUAGE plpgsql;
        ");
    }

    public function safeDown()
    {
        $params = require(\Yii::getAlias('@app/config/params.php'));
        $dbDefault = $params['dbDefault'];
        
        if (!YII_ENV_DEV) {
            echo $dbDefault['skipMigrateFresh'];
            return true;
        }

        $this->execute("DROP FUNCTION IF EXISTS inventory_inbound(INT, TEXT, INTEGER[]);");
    }
}