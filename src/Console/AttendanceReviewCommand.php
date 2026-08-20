<?php

declare(strict_types=1);

namespace App\Console;

use App\Application\AttendanceReview\AttendanceReviewApplicationService;
use App\Application\AttendanceReview\Command\GenerateAttendanceReviewCommand;
use App\Shared\Enums\RecordStatus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

final class AttendanceReviewCommand extends Command
{
    public function __construct(
        private AttendanceReviewApplicationService $applicationService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('generate:attendance-review')
            ->setDescription('Generate AttendanceReview from AttendanceSummary for a period')
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
            '<info>Generating AttendanceReview for period %d%s...</info>',
            $period,
            $summaryId !== null ? " (summary #{$summaryId})" : ' (all summaries for the period)'
        ));

        $command = GenerateAttendanceReviewCommand::create(
            period: $period,
            status: RecordStatus::DRAFT->value,
            attendanceSummaryId: $summaryId !== null ? (int) $summaryId : null,
        );

        $result = $this->applicationService->generateAllFromAttendanceSummary(command: $command);

        $output->writeln(sprintf(
            "\n<info>Done: %d created, %d skipped, %d no review needed, %d errors.</info>",
            $result->created,
            $result->skipped,
            $result->completed,
            count($result->errors)
        ));

        foreach ($result->errors as $employeeId => $error) {
            $output->writeln(sprintf('  <error>❌ Employee #%d: %s</error>', $employeeId, $error));
        }

        return ExitCode::OK;
    }
}
