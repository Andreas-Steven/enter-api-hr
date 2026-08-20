<?php

declare(strict_types=1);

use App\Console;

return [
    'hello' => Console\HelloCommand::class,
    'seed' => Console\SeederCommand::class,
    'generate:attendance-summary' => Console\AttendanceSummaryCommand::class,
    'generate:attendance-review' => Console\AttendanceReviewCommand::class,
    'generate:payroll-report' => Console\PayrollReportCommand::class,
];
