<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeLeave\Action;

// Application Layer
use App\Application\EmployeeLeave\EmployeeLeaveApplicationService;
use App\Application\EmployeeLeave\Command\AssignEmployeeLeaveCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeLeave\Validation\EmployeeLeaveInputValidator;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Grand-assign endpoint: creates one EmployeeLeave per active employee
 * matching the scope criteria (branch_id, division_id, employee_type_id,
 * or an explicit list of employee_ids).
 */
final class EmployeeLeaveAssignAction
{
    private const ALLOWED_KEYS = [
        'name',
        'leave_type',
        'period_start_date',
        'period_end_date',
        'initial_amount',
        'leave_records',
        'status',
        'sync_mdb',
        'scope',
    ];

    public function __construct(
        private EmployeeLeaveInputValidator $inputValidator,
        private EmployeeLeaveApplicationService $applicationService,
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
            context: ValidationContext::ASSIGN,
        );

        $command = AssignEmployeeLeaveCommand::create(
            name: (string) $params->get('name'),
            leaveType: (string) $params->get('leave_type'),
            periodStartDate: (string) $params->get('period_start_date'),
            periodEndDate: (string) $params->get('period_end_date'),
            initialAmount: (int) $params->get('initial_amount'),
            usedAmount: $params->has('used_amount') ? (int) $params->get('used_amount') : null,
            leaveRecords: $params->has('leave_records') ? (array) $params->get('leave_records') : [],
            status: (int) $params->get('status'),
            scope: (array) $params->get('scope'),
            detailInfo: $params->get('detail_info'),
        );

        $resource = $this->applicationService->getResource();
        $result = $this->applicationService->assign(command: $command);

        return $this->responseFactory->success(
            data: $result->data(),
            meta: $result->meta(),
            translate: Message::create(
                key: 'resource.created',
                params: [
                    'resource' => $resource
                ]
            )
        );
    }
}
