<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceSummary\Action;

// Application Layer
use App\Application\AttendanceSummary\AttendanceSummaryApplicationService;
use App\Application\AttendanceSummary\Command\GenerateAttendanceSummaryCommand;
// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceSummary\Validation\AttendanceSummaryInputValidator;
// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Auto-generates AttendanceSummary records.
 *
 * Input is a single `period` (YYYYMM). The application service builds one
 * summary per active employee from their attendance records and employee rules,
 * keeping any existing summaries for the period untouched. The response reports
 * how many were created, skipped (already existing) and any per-employee errors.
 */
final class AttendanceSummaryCreateAction
{
    private const ALLOWED_KEYS = ['period'];

    public function __construct(
        private AttendanceSummaryInputValidator $inputValidator,
        private AttendanceSummaryApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS,
            )
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = GenerateAttendanceSummaryCommand::create(
            period: (int) $params->get('period'),
            status: RecordStatus::DRAFT->value,
        );

        $resource = $this->applicationService->getResource();
        $result = $this->applicationService->generateAllFromAttendance(command: $command);

        return $this->responseFactory->success(
            data: $result->toArray(),
            translate: Message::create(
                key: 'resource.created',
                params: [
                    'resource' => $resource,
                ],
            ),
        );
    }
}
