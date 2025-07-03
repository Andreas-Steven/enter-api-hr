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
    'slaveID' => null,
    'syncMaster' => 1,
    'syncSlave' => 1,
    'syncMdb' => null,
    'status' => 2,
    'lockVersion' => 1,
    'currency' => 'IDR',
    'optimisticLockingComment' => 'Optimistic Locking',
    'syncMdbComment' => '1: unsync, null: synced',
    'syncMasterComment' => '1: unsync, null: synced',
    'syncSlaveComment' => '1: unsync, null: synced',
    'statusComment' => '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance',
    'purchaseComment' => '0: Inactive, 1: Active, 2: Draft, 3: Completed, 4: Deleted, 5: Maintenance, 6: Approved, 7: Rejected',
    'skipMigrateFresh' => 'Skipping migration/fresh for non-dev environment.\n',

    #Add your new database default values here
    'divisionID' => 1,
    'positionID' => 1,
    'employeeTypeID' => 1,
    'workTimeID' => null,
    'isFlat' => 0,
    'logisticStatus' => 0,
    'saleID' => null,
    'deliveryOrderID' => null,
    'vehicleID' => null,
    'logisticFee' => 0,
    'logisticDate' => null,
    'distance' => 0,
    'weight' => 0,
    'isFlatComment' => '0: False, 1: True',
    'logisticStatusComment' => '0: waiting, 1: scheduled, 2: rescheduled, 3: departed, 4: delivered, 5: canceled'
];

/**
 * is default value for json fields in model
 */
$params['defaultValue'] = [
    'phone' => '/^(62)[1-9][0-9]{7,12}$/',
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
    'items' => ['code', 'price', 'condition', 'description', 'buying_date', 'tax_date', 'inspection_date', 'employee', 'division', 'image'],
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