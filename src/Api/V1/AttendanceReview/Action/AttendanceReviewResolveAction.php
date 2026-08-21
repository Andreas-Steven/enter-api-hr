<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceReview\Action;

// Application Layer
use App\Application\AttendanceReview\AttendanceReviewApplicationService;
use App\Application\AttendanceReview\Command\ResolveAttendanceReviewItemCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceReview\Validation\AttendanceReviewInputValidator;

// Shared Layer
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

/**
 * Resolves a single AttendanceReview case.
 *
 * Input:
 *   POST /v1/attendance-review/{id}/resolve
 *   {
 *     "resolved_treatment": "TREAT_AS_ABSENT",
 *     "note": "Karyawan tidak hadir, ditandai absen",
 *     "lock_version": 1
 *   }
 *
 * The reviewer chooses a treatment from the evidence's `treatment_options`.
 * The entity records the resolved_treatment, sets review_status to
 * CLOSE (2) and transitions the resource status to COMPLETED (3). The
 * detail_info is updated with approved_at / approved_by via
 * DetailInfoFactory::withApproved().
 */
final class AttendanceReviewResolveAction
{
    private const ALLOWED_KEYS = ['resolved_treatment', 'note', 'lock_version'];

    public function __construct(
        private AttendanceReviewInputValidator $inputValidator,
        private AttendanceReviewApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface {
        /** @var \App\Shared\Request\RequestParams|null $payload */
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

        $payload->ensureExists(
            resource: $resource
        );

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('id', $id)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::APPROVE,
        );

        $command = ResolveAttendanceReviewItemCommand::create(
            reviewId: (int) $id,
            resolvedTreatment: (string) $params->get('resolved_treatment'),
            note: $params->get('note'),
            lockVersion: $params->get('lock_version') !== null
                ? (int) $params->get('lock_version')
                : null,
        );

        $response = $this->applicationService->resolveItem(
            command: $command,
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'attendance_review.item_resolved',
                domain: 'app',
                params: [
                    'resource' => $resource,
                    'id' => $command->reviewId,
                ]
            ),
        );
    }
}
