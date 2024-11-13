<?php

return [
    'titleService' => 'Enter-HR',
    'timeZone' => 'Asia/Jakarta',
    'extraCookies' => 'name-cookie-01',
    'defaultLanguage' => 'en',
    'codeApp' => 'enterXXX',
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
    ],

    'adminEmail' => 'admin@example.com',
    'senderEmail' => 'noreply@example.com',
    'senderName' => 'Example.com mailer',

    "items" => [
        "code" => null,
        "price" => null,
        "condition" => null,
        "description" => null,
        "buying_date" => null,
        "tax_date" => null,
        "inspection_date" => null,
        "divison" => null,
        "image" => null,
        "items_change_log" => null
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
    ]
];
