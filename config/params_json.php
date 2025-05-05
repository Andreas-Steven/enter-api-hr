<?php

/**
 * Parameters configuration for the application.
 * 
 * Version: 1.0.0
 * Version Date: 2025-05-05
 */

/**
 * is default value for database fields in model and table migration
 */
$params['dbDefault'] = [
    'createdBy' => 'system',
    'masterID' => null,
    'syncMaster' => 1,
    'slaveID' => null,
    'syncSlave' => 1,
    'currency' => 'IDR',
    'statusComment' => '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance',
    'purchaseComment' => '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance, 6: Approved, 7: Rejected',
];

/**
 * is default value for json fields in model
 */
$params['defaultValue'] = [
    'items' => [
        'code' => null,
        'price' => null,
        'condition' => null,
        'description' => null,
        'buying_date' => null,
        'tax_date' => null,
        'inspection_date' => null,
        'employee' => null,
        'division' => null,
        'image' => [],
        'items_change_log' => [
            'created_at' => null,
            'created_by' => null, 
            'updated_at' => null,
            'updated_by' => null,
            'deleted_at' => null,
            'deleted_by' => null
        ]
    ],
    'attendance' => [
        'employee_id' => null,
        'employee_name' => null,
        'start_time' => null,
        'end_time' => null,
        'device' => null,
        'division' => null,
        'work_time' => null,
        'location' => null,
        'info' => [
            'in' => null,
            'out' => null,
            'detail' => [],
        ],
    ],
    'revision' => [
        'start_time' => null,
        'end_time' => null,
    ],
];

/**
 * is allowed fields for json data in model
 */
$params['allowedFields'] = [
    'attendance' => ['employee_id', 'start_time', 'end_time', 'device', 'location'],
    'attendance_location' => ['lat', 'lng', 'image'],
    'location' => ['lat', 'lng'],
    'revision' => ['start_time', 'end_time'],
    'working_days' => [1, 2, 3, 4, 5, 6, 7],
    'time_structure' => ['entry_time', 'home_time'],
    'items' => ['code', 'price', 'condition', 'description', 'buying_date', 'tax_date', 'inspection_date', 'employee', 'division', 'image', 'items_change_log'],
];

/**
 * checking table field if already used in another table for update action.
 */
$params['dependenciesUpdate'] = [
    /* Employee Module */
    'division' => ['code', 'name', 'status'],
    'employee_position' => ['name', 'status'],
    'employee_type' => ['name', 'status'],

    /* General Affair Module */
    'general_affair_product' => ['name', 'status'],
    'general_affair_location' => ['name', 'status'],
    'general_affair_category' => ['name', 'status'],

    /* Attendance Module */
    'work_time' => ['name', 'working_days', 'status'],
    'employee' => ['name', 'status'],
];

/**
 * any prefix for table field unique data.
 */
$params['prefix'] = [
    'phone_number' => "62",
];

return $params;