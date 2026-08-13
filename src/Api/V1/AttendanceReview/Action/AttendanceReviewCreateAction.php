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
 * Auto-generates AttendanceReview items for all employees in a period.
 *
 * Loads every AttendanceSummary for the given period and scans each one
 * for review-triggering treatments (MANUAL_REVIEW, REQUIRE_APPROVAL,
 * SEND_TO_REVIEW). Creates AttendanceReview entities for any that don't
 * already exist (keep-by-stable-key).
 */
final class AttendanceReviewCreateAction
{
    private const ALLOWED_KEYS = [
        'period',
        'missing_in_policy',
        'missing_out_policy',
        'status',
        'detail_info',
        'sync_mdb',
    ];

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
            )->with('status', RecordStatus::DRAFT->value)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $missingPolicyConfig = null;
        if ($params->get('missing_in_policy') !== null || $params->get('missing_out_policy') !== null) {
            $missingPolicyConfig = [
                'missing_in_policy' => $params->get('missing_in_policy'),
                'missing_out_policy' => $params->get('missing_out_policy'),
            ];
        }

        $command = GenerateAttendanceReviewCommand::create(
            period: (int) $params->get('period'),
            missingPolicyConfig: $missingPolicyConfig,
        );

        $resource = $this->applicationService->getResource();
        $result = $this->applicationService->generate(command: $command);

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
