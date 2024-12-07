<?php

return [
    'titleService' => 'enterkomputer-human-resource',
    'serviceVersion' => 'V1',
    'timeZone' => 'Asia/Jakarta',
    'extraCookies' => 'name-cookie-01',
    'codeApp' => 'enterHR',
    'appRoles' => ['enterHR'],
    'defaultLanguage' => 'en',
    'languages' => [
        'en', 
        'id'
    ],
    'jwt' => [
        'key' => 'secret-key-harus-panjang-256-bit',
        'algorithm' => 'HS256',
        'expire' => '+1 hour',
        'issuer' => 'https://sso.dev-enterkomputer.com',
        'audience' => 'https://sso.dev-enterkomputer.com',
        'id' => 'sso-auth-v1',
        'request_time' => '+5 seconds',
        'authException' => '*',
    ],
    'request' => [
        'enableCsrfValidation' => false,
    ],
    'cors' => [
        'origins' => ['http://localhost:5173', 'https://entersys.dev-enterkomputer.com'],
        'requestMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
        'allowHeaders' => ['Origin','Content-Type','Authorization','Accept-Language'],
    ],
    'pagination' => [
        'pageSize' => 10,
        'sortDir' => SORT_DESC,
    ],
    'verbsAction' => [
        'index' => ['get'],
        'create' => ['post'],
        'update' => ['put'],
        'delete' => ['delete'],
    ],
    'mailer' => [
        'adminEmail' => 'admin@example.com',
        'senderEmail' => 'noreply@example.com',
        'senderName' => 'Example.com mailer',
    ],
    
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
