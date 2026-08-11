<?php

declare(strict_types=1);

namespace App\Console;

use App\Application\AttendanceSummary\AttendanceSummaryApplicationService;
use App\Application\AttendanceSummary\Command\GenerateAttendanceSummaryCommand;
use App\Shared\Enums\RecordStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

final class AttendanceSummaryGenerateCommand extends Command
{
    public function __construct(
        private AttendanceSummaryApplicationService $applicationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:attendance-summary')
            ->setDescription('Generate AttendanceSummary from Attendance data for all active employees')
            ->addOption(
                'period',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Period in YYYYMM format (default: previous month)',
                null
            )
            ->addOption(
                'employee-id',
                'e',
                InputOption::VALUE_OPTIONAL,
                'Specific employee ID (default: all active employees)',
                null
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $period = $input->getOption('period');
        if ($period === null) {
            $period = (int) date('Ym', strtotime('first day of previous month'));
        } else {
            $period = (int) $period;
        }

        $employeeId = $input->getOption('employee-id');

        $output->writeln(sprintf(
            '<info>Generating AttendanceSummary for period %d%s...</info>',
            $period,
            $employeeId !== null ? " (employee #{$employeeId})" : ' (all active employees)'
        ));

        $command = GenerateAttendanceSummaryCommand::create(
            period: $period,
            status: RecordStatus::DRAFT->value,
            employeeId: $employeeId !== null ? (int) $employeeId : null,
        );

        $result = $this->applicationService->generateAllFromAttendance(command: $command);

        $output->writeln(sprintf(
            "\n<info>Done: %d created, %d skipped, %d errors.</info>",
            $result->created,
            $result->skipped,
            count($result->errors)
        ));

        foreach ($result->errors as $empId => $error) {
            $output->writeln(sprintf('  <error>❌ Employee #%d: %s</error>', $empId, $error));
        }

        return ExitCode::OK;
    }
}
