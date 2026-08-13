<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceReview\Action;

// Application Layer
use App\Application\AttendanceReview\AttendanceReviewApplicationService;
use App\Application\AttendanceReview\Command\ResolveAttendanceReviewCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceReview\Validation\AttendanceReviewInputValidator;

// Shared Layer
use App\Domain\Shared\Enums\ApprovalStatus;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

/**
 * Resolves a review item (approve/reject) with optional treatment override.
 *
 * When approving, the resolver provides a `resolved_treatment` — the
 * alternative treatment to apply (e.g. TREAT_AS_ABSENT for a deficit
 * that was originally MANUAL_REVIEW).
 *
 * The resolution cascades back to the source AttendanceSummary and
 * PayrollReport via full recalculation with overrides.
 */
final class AttendanceReviewResolveAction
{
    private const ALLOWED_KEYS = ['decision', 'resolved_treatment', 'approved_note', 'lock_version'];

    public function __construct(
        private AttendanceReviewInputValidator $inputValidator,
        private AttendanceReviewApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface
    {
        $id = $currentRoute->getArgument('id');
        $payload = $request->getAttribute('payload');
        $resource = $this->applicationService->getResource();

        if ($id === null) {
            return $this->responseFactory->fail(
                translate: Message::create(
                    key: 'route.parameter_missing',
                    params: [
                        'resource' => $resource,
                        'parameter' => 'id',
                    ]
                ),
                httpCode: Status::NOT_FOUND
            );
        }

        $payload?->ensureExists(
            resource: $resource
        );

        $params = $payload?->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('id', $id)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::APPROVE,
        );

        $decisionStr = (string) $params->get('decision');
        $decision = match (strtoupper($decisionStr)) {
            'APPROVE', 'APPROVED' => ApprovalStatus::APPROVED,
            'REJECT', 'REJECTED' => ApprovalStatus::REJECTED,
            default => ApprovalStatus::APPROVED,
        };

        $command = ResolveAttendanceReviewCommand::create(
            id: (int) $id,
            decision: $decision,
            resolvedTreatment: $params->get('resolved_treatment'),
            approvedNote: $params->get('approved_note'),
            lockVersion: $params->get('lock_version') !== null ? (int) $params->get('lock_version') : null,
        );

        $response = $this->applicationService->resolve(
            id: (int) $id,
            command: $command
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.updated',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
