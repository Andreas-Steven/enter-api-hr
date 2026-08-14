<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceSummary\Action;

// Application Layer
use App\Application\AttendanceSummary\AttendanceSummaryApplicationService;
use App\Application\AttendanceSummary\Command\UpdateAttendanceSummaryCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceSummary\Validation\AttendanceSummaryInputValidator;

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
 * AttendanceSummary Update API Action
 */
final class AttendanceSummaryUpdateAction
{
    private const ALLOWED_KEYS = [
        'name',
        'employee_id',
        'branch_id',
        'division_id',
        'position_id',
        'employee_type_id',
        'work_pattern_id',
        'period',
        'attendance_detail',
        'permission_detail',
        'period_summary',
        'time_summary',
        'notes',
        'status',
        'detail_info',
        'lock_version',
    ];

    public function __construct(
        private AttendanceSummaryInputValidator $inputValidator,
        private AttendanceSummaryApplicationService $applicationService,
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

        $command = UpdateAttendanceSummaryCommand::create(
            id: (int) $id,
            name: $params->get('name'),
            employeeId: $params->get('employee_id') !== null ? (int) $params->get('employee_id') : null,
            branchId: $params->get('branch_id') !== null ? (int) $params->get('branch_id') : null,
            divisionId: $params->get('division_id') !== null ? (int) $params->get('division_id') : null,
            positionId: $params->get('position_id') !== null ? (int) $params->get('position_id') : null,
            employeeTypeId: $params->get('employee_type_id') !== null ? (int) $params->get('employee_type_id') : null,
            workPatternId: $params->get('work_pattern_id') !== null ? (int) $params->get('work_pattern_id') : null,
            period: $params->get('period') !== null ? (int) $params->get('period') : null,
            attendanceDetail: $params->get('attendance_detail'),
            permissionDetail: $params->get('permission_detail'),
            periodSummary: $params->get('period_summary'),
            timeSummary: $params->get('time_summary'),
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
