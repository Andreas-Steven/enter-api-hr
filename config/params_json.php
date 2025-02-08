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
        'attendance_field' => [
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
            ]
        ],
        'info' => [
            'LT' => 'Late',
            'LE' => 'Leave Early',
            'ICC' => 'Incomplate Check Clock',
            'WH' => 'Work Holiday',
        ],
    ],
];

/**
 * is allowed fields for json data in model
 */
$params['allowedFields'] = [
    
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