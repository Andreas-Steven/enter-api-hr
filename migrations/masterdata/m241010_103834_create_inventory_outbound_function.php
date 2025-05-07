<?php

use yii\db\Migration;

/**
 * Handles adding the inventory_inbound function to the inventory table.
 */
class m241010_103834_create_inventory_outbound_function extends Migration
{
    public function safeUp()
    {
        $params = require(\Yii::getAlias('@app/config/params.php'));
        $dbDefault = $params['dbDefault'];
        
        if (!YII_ENV_DEV) {
            echo $dbDefault['skipMigrateFresh'];
            return true;
        }

        // return;
        // Example used of function SELECT code, new_quantity, old_quantity, md_data, message, product_id, inventory_id, outbound_quantity FROM inventory_outbound(1, '1', 10);

        $this->execute("
            CREATE OR REPLACE FUNCTION inventory_outbound(
                out_inventory_id INT,
                out_product_id INT,
                out_outbound_quantity INT
            ) RETURNS TABLE(
                code INTEGER, 
                new_quantity INTEGER,
                old_quantity INTEGER,
                message TEXT, 
                md_data TEXT,
                product_id INT,
                inventory_id INT,
                outbound_quantity INT
            ) AS $$
            DECLARE
                outbound_md_data INTEGER[];
                remaining_md_data INTEGER[];
                old_quantity INT;
                new_quantity INT;
                message TEXT;
                payload JSON := '[]'::json;
            BEGIN
                new_quantity := NULL;
                old_quantity := NULL;
                message := NULL;

                -- Construct the payload (although it's not returned, it's still used internally)
                payload := jsonb_build_object(
                    'inventory_id', out_inventory_id,
                    'product_id', out_product_id,
                    'outbound_quantity', out_outbound_quantity
                )::json;

                -- Retrieve the current md_data
                SELECT array_agg(elem::INT ORDER BY elem::INT) INTO remaining_md_data
                FROM inventory, jsonb_array_elements_text(detail_item->out_product_id::text->'md_data') AS elem
                WHERE id = out_inventory_id;

                IF remaining_md_data IS NULL THEN
                    RETURN QUERY SELECT 
                        400 AS code, 
                        0 AS new_quantity,
                        0 AS old_quantity,
                        'Product in the inventory is not found or empty data.' AS message, 
                        '[]' AS md_data,
                        out_product_id AS product_id,
                        out_inventory_id AS inventory_id,
                        out_outbound_quantity AS outbound_quantity;
                    RETURN;
                END IF;

                -- Retrieve the old quantity
                SELECT array_length(remaining_md_data, 1) INTO old_quantity;

                -- Select the specified number of values from md_data in ascending order
                SELECT array(
                    SELECT elem::INT
                    FROM unnest(remaining_md_data) AS elem
                    ORDER BY elem
                    LIMIT out_outbound_quantity
                ) INTO outbound_md_data;

                -- Remove the selected values from md_data
                remaining_md_data := ARRAY(
                    SELECT elem
                    FROM unnest(remaining_md_data) AS elem
                    WHERE elem NOT IN (SELECT unnest(outbound_md_data))
                    ORDER BY elem
                );

                -- Update the new size of md_data in quantity
                new_quantity := COALESCE(array_length(remaining_md_data, 1), 0);

                -- Update the inventory table with the new md_data and quantity
                UPDATE inventory
                SET detail_item = jsonb_set(
                    jsonb_set(detail_item, ARRAY[out_product_id::text, 'md_data'], to_jsonb(remaining_md_data)),
                    ARRAY[out_product_id::text, 'quantity'],
                    to_jsonb(new_quantity)
                )
                WHERE id = out_inventory_id;

                -- Convert outbound_md_data to a text representation
                md_data := array_to_json(outbound_md_data)::TEXT;

                RAISE NOTICE 'Outbound md_data: %', outbound_md_data;
                RAISE NOTICE 'Old quantity: %', old_quantity;
                RAISE NOTICE 'New quantity: %', new_quantity;

                RETURN QUERY SELECT 
                    200 AS code, 
                    new_quantity, 
                    old_quantity,
                    NULL AS message, 
                    md_data,
                    out_product_id AS product_id,
                    out_inventory_id AS inventory_id,
                    out_outbound_quantity AS outbound_quantity;
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
        
        $this->execute("DROP FUNCTION IF EXISTS inventory_outbound(INT, TEXT, INT);");
    }
}