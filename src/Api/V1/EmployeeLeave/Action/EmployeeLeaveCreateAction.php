<?php

declare(strict_types=1);

namespace App\Api\V1\EmployeeLeave\Action;

// Application Layer
use App\Application\EmployeeLeave\EmployeeLeaveApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\EmployeeLeave\Validation\EmployeeLeaveInputValidator;
use App\Application\EmployeeLeave\Command\CreateEmployeeLeaveCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class EmployeeLeaveCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'leave_type', 'employee_id', 'period_start_date', 'period_end_date', 'total_amount', 'adjustment_amount', 'leave_records', 'status', 'sync_mdb'];

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
            )->with('status', RecordStatus::DRAFT->value)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = CreateEmployeeLeaveCommand::create(
            name: (string) $params->get('name'),
            leaveType: (string) $params->get('leave_type'),
            employeeId: (int) $params->get('employee_id'),
            periodStartDate: (string) $params->get('period_start_date'),
            periodEndDate: (string) $params->get('period_end_date'),
            totalAmount: (int) $params->get('total_amount'),
            adjustmentAmount: $params->has('adjustment_amount') ? (int) $params->get('adjustment_amount') : null,
            usedAmount: $params->has('used_amount') ? (int) $params->get('used_amount') : null,
            leaveRecords: $params->has('leave_records') ? (array) $params->get('leave_records') : [],
            status: (int) $params->get('status'),
            detailInfo: $params->get('detail_info')
        );

        $resource = $this->applicationService->getResource();
        $response = $this->applicationService->create(command: $command);

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.created',
                params: [
                    'resource' => $resource
                ]
            )
        );
    }
}
