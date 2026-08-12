<?php

declare(strict_types=1);

use App\Console;

return [
    'hello' => Console\HelloCommand::class,
    'seed' => Console\SeederCommand::class,
    'generate:attendance-summary' => Console\AttendanceSummaryGenerateCommand::class,
    'generate:payroll-report' => Console\PayrollReportGenerateCommand::class,
];
