<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeLeave\Action;

// Application Layer
use App\Application\EmployeeLeave\EmployeeLeaveApplicationService;
use App\Application\EmployeeLeave\Command\AdjustEmployeeLeaveCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeLeave\Validation\EmployeeLeaveInputValidator;

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
 * Appends a single adjustment transaction to an existing EmployeeLeave record.
 *
 * Endpoint: PUT /v1/employee-leave/{id}/adjustment
 */
final class EmployeeLeaveAdjustAction
{
    private const ALLOWED_KEYS = [
        'adjustment_amount',
        'adjustment_type',
        'adjustment_leave_type',
        'adjustment_note',
        'lock_version',
    ];

    public function __construct(
        private EmployeeLeaveInputValidator $inputValidator,
        private EmployeeLeaveApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface {
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
            context: ValidationContext::ADJUST,
        );

        $command = AdjustEmployeeLeaveCommand::create(
            id: (int) $id,
            amount: (int) $params->get('adjustment_amount'),
            adjustmentType: (string) $params->get('adjustment_type'),
            adjustmentLeaveType: (string) $params->get('adjustment_leave_type'),
            note: $params->get('adjustment_note'),
            lockVersion: (int) $params->get('lock_version'),
        );

        $response = $this->applicationService->adjust(
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
