<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceReview\Action;

// Application Layer
use App\Application\AttendanceReview\AttendanceReviewApplicationService;
use App\Application\AttendanceReview\Command\GenerateAttendanceReviewCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceReview\Validation\AttendanceReviewInputValidator;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Auto-generates AttendanceReview records.
 *
 * Input is a single `period` (YYYYMM). The application service scans each
 * AttendanceSummary for the period and builds one review per summary
 * containing the review-triggering items (missing in/out with SEND_TO_REVIEW
 * policy, deficit with MANUAL_REVIEW treatment, excess with REQUIRE_APPROVAL
 * treatment). The response reports how many were created, skipped, completed
 * (no items) and any per-summary errors.
 */
final class AttendanceReviewCreateAction
{
    private const ALLOWED_KEYS = ['period'];

    public function __construct(
        private AttendanceReviewInputValidator $inputValidator,
        private AttendanceReviewApplicationService $applicationService,
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

        $command = GenerateAttendanceReviewCommand::create(
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
