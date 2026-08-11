<?php

declare(strict_types=1);

namespace App\Api\V1\PayrollReports\Action;

// Application Layer
use App\Application\PayrollReports\PayrollReportsApplicationService;
use App\Application\PayrollReports\Command\UpdatePayrollReportsCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\PayrollReports\Validation\PayrollReportsInputValidator;

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
 * PayrollReports Update API Action
 */
final class PayrollReportsUpdateAction
{
    private const ALLOWED_KEYS = [
        'name',
        'attendance_summary_id',
        'payroll_summary',
        'notes',
        'status',
        'detail_info',
        'lock_version',
    ];

    public function __construct(
        private PayrollReportsInputValidator $inputValidator,
        private PayrollReportsApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface
    {
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
            context: ValidationContext::UPDATE,
        );

        $command = UpdatePayrollReportsCommand::create(
            id: (int) $id,
            name: $params->get('name'),
            attendanceSummaryId: $params->get('attendance_summary_id') !== null ? (int) $params->get('attendance_summary_id') : null,
            payrollSummary: $params->get('payroll_summary'),
            notes: $params->get('notes'),
            status: $params->get('status') !== null ? (int) $params->get('status') : null,
            detailInfo: $params->get('detail_info'),
            lockVersion: $params->get('lock_version') !== null ? (int) $params->get('lock_version') : null,
        );

        $response = $this->applicationService->update(
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
