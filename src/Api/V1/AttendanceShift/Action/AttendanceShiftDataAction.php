<?php

declare(strict_types=1);

namespace App\Api\V1\AttendanceShift\Action;

// Application Layer
use App\Application\AttendanceShift\AttendanceShiftApplicationService;
use App\Application\Shared\Factory\SearchCriteriaFactory;
// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AttendanceShift\Validation\AttendanceShiftInputValidator;
// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RequestParams;
use App\Shared\Context\ValidationContext;
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
// Vendor Layer
use DateTimeImmutable;

final class AttendanceShiftDataAction
{
    private const ALLOWED_KEYS = ['id', 'name', 'start_time', 'end_time', 'break_minutes', 'cross_day', 'note', 'status'];
    private const ALLOWED_SORT = [
        'id' => 'id',
        'name' => 'name',
        'start_time' => 'start_time',
        'end_time' => 'end_time',
        'break_minutes' => 'break_minutes',
        'cross_day' => 'cross_day',
        'note' => 'note',
        'status' => 'status',
    ];

    public function __construct(
        private SearchCriteriaFactory $factory,
        private AttendanceShiftInputValidator $inputValidator,
        private AttendanceShiftApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $filter = $payload->getFilter()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS,
            )->with('status', RecordStatus::DRAFT->value);

        $this->inputValidator->validate(
            data: $filter,
            context: ValidationContext::SEARCH,
        );

        $filterArray = $payload->getFilter()
            ->onlyAllowed(allowedKeys: self::ALLOWED_KEYS)
            ->toArray();

        $filterArray = $this->parseTimeRange($filterArray, 'start_time');
        $filterArray = $this->parseTimeRange($filterArray, 'end_time');

        $parsedPayload = RequestParams::from(
            data: [...$payload->all(), 'filter' => $filterArray],
        );

        $criteria = $this->factory->createFromRequest(
            params: $parsedPayload,
            allowedSort: self::ALLOWED_SORT,
        );

        $resource = $this->applicationService->getResource();
        $result = $this->applicationService->list(criteria: $criteria);

        return $this->responseFactory->success(
            data: $result->data,
            translate: Message::create(
                key: 'resource.list_retrieved',
                params: [
                    'resource' => $resource,
                ],
            ),
            meta: $result->getMeta(),
        );
    }

    /**
     * Parse a comma-separated time range string into a min/max array.
     *
     * Example: "06:00:00,08:00:00" becomes ['min' => '06:00:00', 'max' => '08:00:00'].
     *
     * @param array<string, mixed> $filter
     * @return array<string, mixed>
     */
    private function parseTimeRange(array $filter, string $field): array
    {
        if (!isset($filter[$field]) || !is_string($filter[$field]) || $filter[$field] === '' || !str_contains($filter[$field], ',')) {
            return $filter;
        }

        $parts = explode(',', $filter[$field]);

        if (count($parts) !== 2) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'time.range_format',
                    params: [
                        'field' => $field,
                        'format' => 'HH:MM:SS,HH:MM:SS',
                    ],
                    domain: 'validation',
                ),
            );
        }

        $min = trim($parts[0]);
        $max = trim($parts[1]);

        foreach ([$min, $max] as $timeValue) {
            $time = DateTimeImmutable::createFromFormat('H:i:s', $timeValue);

            if (!$time || $time->format('H:i:s') !== $timeValue) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'time.range_format',
                        params: [
                            'field' => $field,
                            'format' => 'HH:MM:SS,HH:MM:SS',
                        ],
                        domain: 'validation',
                    ),
                );
            }
        }

        $filter[$field] = [
            'min' => $min,
            'max' => $max,
        ];

        return $filter;
    }
}
