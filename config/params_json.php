<?php

/**
 * is default value for json fields in model
 */
$params['defaultValue'] = [
    /* Example */
    // 'vendor' => [
    //     'address' => null,
    //     'pic' => [
    //         'name' => null,
    //         'phone' => null,
    //         'note' => null,
    //     ],
    // ],

    "items" => [
        "code" => null,
        "price" => null,
        "condition" => null,
        "description" => null,
        "buying_date" => null,
        "tax_date" => null,
        "inspection_date" => null,
        "division" => null,
        "image" => [],
        "items_change_log" => [
            'created_at' => null,
            'created_by' => null, 
            'updated_at' => null,
            'updated_by' => null,
            'deleted_at' => null,
            'deleted_by' => null
        ]
    ],
    'attendance' => [
        "employee_id" => null,
        "employee_name" => null,
        "start_time" => null,
        "end_time" => null,
        "device" => null,
        "division" => null,
        "work_time" => null,
        "location" => null,
        "info" => [
            'in' => null,
            'out' => null,
            'detail' => [],
        ],
    ],
    'salary_detail' => [
        "employee_id" => null,
        "employee_name" => null,
        "division" => [
            "id" => null,
            "name" => null,
        ],
        "absences" => [
            "sick" => null,
            "leave" => null,
        ],
        "earnings" => [
            "bonuses" => 0,
            "allowances" => 0,
            "overtime_pay" => 0,
            "reimbursements" => 0,
        ],
        "deductions" => [
            "tax" => 0,
            "insurance" => 0,
            "loan" => 0,
            "absent" => 0,
            "early_leave" => 0,
            "late" => 0,
        ],
        "summary" => [
            "base_salary" => null,
            "total_earnings" => null,
            "gross_salary" => null,
            "total_deductions" => null,
            "net_salary" => null,
        ]
    ]
];

/**
 * is allowed fields for json data in model
 */
$params['allowedFields'] = [
    // 'purchase' => [
    //     'product' => ['id', 'quantity', 'price'],
    // ],
];

/**
 * checking table field if already used in another table for update action.
 */
$params['dependenciesUpdate'] = [
    /* Example */
    // 'vendor' => ['name', 'status'],

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
    'employee' => ['name'],
];

/**
 * any prefix for table field unique data.
 */
$params['prefix'] = [
    /* Example */
    // 'purchase' => "PO",
];

return $params;