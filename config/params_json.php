<?php

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
    'working_days' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
    'time_structure' => ['entry_time', 'home_time'],
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
    'work_time' => ['entry_time', 'home_time', 'status'],
    'employee' => ['name', 'status'],
];

/**
 * any prefix for table field unique data.
 */
$params['prefix'] = [
    'phone_number' => "62",
];

return $params;