<?php

declare(strict_types=1);

namespace App\Console;

use App\Application\PayrollReport\PayrollReportApplicationService;
use App\Application\PayrollReport\Command\GeneratePayrollReportCommand;
use App\Shared\Enums\RecordStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

final class PayrollReportCommand extends Command
{
    public function __construct(
        private PayrollReportApplicationService $applicationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:payroll-report')
            ->setDescription('Generate PayrollReport from AttendanceSummary for all active employees')
            ->addOption(
                'period',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Period in YYYYMM format (default: previous month)',
                null
            )
            ->addOption(
                'attendance-summary-id',
                's',
                InputOption::VALUE_OPTIONAL,
                'Specific AttendanceSummary ID (default: all summaries for the period)',
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

        $summaryId = $input->getOption('attendance-summary-id');

        $output->writeln(sprintf(
            '<info>Generating PayrollReport for period %d%s...</info>',
            $period,
            $summaryId !== null ? " (summary #{$summaryId})" : ' (all summaries for the period)'
        ));

        $command = GeneratePayrollReportCommand::create(
            period: $period,
            status: RecordStatus::DRAFT->value,
            attendanceSummaryId: $summaryId !== null ? (int) $summaryId : null,
        );

        $result = $this->applicationService->generateAllFromAttendanceSummary(command: $command);

        $output->writeln(sprintf(
            "\n<info>Done: %d created, %d skipped, %d updated, %d errors.</info>",
            $result->created,
            $result->skipped,
            $result->updated,
            count($result->errors)
        ));

        foreach ($result->errors as $summaryId => $error) {
            $output->writeln(sprintf('  <error>❌ Summary #%d: %s</error>', $summaryId, $error));
        }

        return ExitCode::OK;
    }
}
