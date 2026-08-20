<?php

declare(strict_types=1);

namespace App\Api\V1\Attendance\Action;

// Application Layer
use App\Application\Attendance\AttendanceApplicationService;
use App\Application\AttendancePermission\AttendancePermissionApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\Attendance\Validation\AttendanceInputValidator;
use App\Domain\Attendance\ValueObject\AttendancePeriod;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

final class AttendanceShowAction
{
    private const ALLOWED_KEYS = ['employee_id', 'date'];

    public function __construct(
        private AttendanceInputValidator $inputValidator,
        private AttendanceApplicationService $applicationService,
        private AttendancePermissionApplicationService $attendancePermissionApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');
        $routeEmployeeId = $currentRoute->getArgument('id');

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            );

        $filter = $payload->getFilter()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            );

        $data = $routeEmployeeId !== null
            ? $params->with('employee_id', $routeEmployeeId)
            : ($filter->has('employee_id') ? $filter : $params);

        if ($routeEmployeeId === null && !$data->has('employee_id')) {
            return $this->responseFactory->fail(
                translate: Message::create(
                    key: 'route.parameter_missing',
                    params: [
                        'resource' => $this->applicationService->getResource(),
                        'parameter' => 'id',
                    ]
                ),
                httpCode: Status::NOT_FOUND
            );
        }

        $this->inputValidator->validate(
            data: $data,
            context: ValidationContext::ATTENDANCE_SHOW,
        );

        $employeeId = (int) ($data->get('employee_id') ?? 0);
        $dateString = (string) ($data->get('date') ?? '');

        $period = AttendancePeriod::fromDateString(
            $dateString
        )->toInt();

        $attendance = $this->applicationService->reportData(
            employeeId: $employeeId,
            period: $period
        );

        $permission = $this->attendancePermissionApplicationService->reportData(
            employeeId: $employeeId,
            period: $period
        );

        // Filter attendance to the specific date
        $attendance = array_values(array_filter(
            $attendance,
            static fn(array $row): bool => ($row['date'] ?? null) === $dateString
        ));

        // Filter permission to entries active on the specific date
        $permission = array_values(array_filter(
            $permission,
            static function (array $row) use ($dateString): bool {
                $startDate = $row['start_date'] ?? null;
                $endDate = $row['end_date'] ?? null;

                if ($startDate === null || $startDate > $dateString) {
                    return false;
                }

                return $endDate === null || $endDate >= $dateString;
            }
        ));

        $data = [
            'attendance' => $attendance,
            'permission' => $permission,
        ];

        $attendanceMeta = new PaginatedResult(
            data: $attendance,
            total: count($attendance),
            page: 1,
            pageSize: count($attendance) > 0 ? count($attendance) : 10,
            filter: ['employee_id' => $employeeId, 'date' => $dateString],
            sort: ['by' => 'date', 'dir' => 'asc']
        );

        $permissionMeta = new PaginatedResult(
            data: $permission,
            total: count($permission),
            page: 1,
            pageSize: count($permission) > 0 ? count($permission) : 10,
            filter: ['employee_id' => $employeeId, 'date' => $dateString],
            sort: ['by' => 'date', 'dir' => 'asc']
        );

        return $this->responseFactory->success(
            data: $data,
            meta: [
                'attendance' => $attendanceMeta->getMeta(),
                'permission' => $permissionMeta->getMeta(),
            ],
            translate: Message::create(
                key: 'resource.list_retrieved',
                params: [
                    'resource' => $this->applicationService->getResource()
                ]
            ),
        );
    }
}
