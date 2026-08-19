<?php

declare(strict_types=1);

namespace App\Api\V1\PayrollReport\Action;

// Application Layer
use App\Application\PayrollReport\PayrollReportApplicationService;
use App\Application\PayrollReport\Command\GeneratePayrollReportCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\PayrollReport\Validation\PayrollReportInputValidator;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Auto-generates PayrollReport records.
 *
 * Input is a single `period` (YYYYMM). The application service builds one
 * payroll report per active employee from their AttendanceSummary records and
 * the configured AttendanceFineRate deduction rates. Existing reports for the
 * period are recalculated (updated) instead of duplicated. The response reports
 * how many were created, skipped, updated and any per-summary errors.
 */
final class PayrollReportCreateAction
{
    private const ALLOWED_KEYS = ['period'];

    public function __construct(
        private PayrollReportInputValidator $inputValidator,
        private PayrollReportApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = GeneratePayrollReportCommand::create(
            period: (int) $params->get('period'),
            status: RecordStatus::DRAFT->value,
        );

        $resource = $this->applicationService->getResource();
        $result = $this->applicationService->generateAllFromAttendanceSummary(command: $command);

        return $this->responseFactory->success(
            data: $result->toArray(),
            translate: Message::create(
                key: 'resource.created',
                params: [
                    'resource' => $resource
                ]
            )
        );
    }
}
