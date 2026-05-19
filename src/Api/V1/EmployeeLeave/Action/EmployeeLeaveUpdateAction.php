<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeLeave\Action;

// Application Layer
use App\Application\EmployeeLeave\EmployeeLeaveApplicationService;
use App\Application\EmployeeLeave\Command\UpdateEmployeeLeaveCommand;

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
 * EmployeeLeave Update API Action
 */
final class EmployeeLeaveUpdateAction
{
    private const ALLOWED_KEYS = ['name', 'leave_type', 'employee_id', 'period_start_date', 'period_end_date', 'total_amount', 'adjustment_amount', 'used_amount', 'leave_records', 'status', 'lock_version'];

    public function __construct(
        private EmployeeLeaveInputValidator $inputValidator,
        private EmployeeLeaveApplicationService $applicationService,
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

        $command = UpdateEmployeeLeaveCommand::create(
            id: (int) $id,
            name: $params->get('name'),
            leaveType: $params->get('leave_type'),
            employeeId: $params->has('employee_id') ? (int) $params->get('employee_id') : null,
            periodStartDate: $params->get('period_start_date'),
            periodEndDate: $params->get('period_end_date'),
            totalAmount: $params->has('total_amount') ? (int) $params->get('total_amount') : null,
            adjustmentAmount: $params->has('adjustment_amount') ? (int) $params->get('adjustment_amount') : null,
            usedAmount: $params->has('used_amount') ? (int) $params->get('used_amount') : null,
            leaveRecords: $params->has('leave_records') ? (array) $params->get('leave_records') : null,
            status: $params->has('status') ? (int) $params->get('status') : null,
            detailInfo: $params->get('detail_info'),
            lockVersion: (int) $params->get('lock_version'),
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
