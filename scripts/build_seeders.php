<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$baseDir = __DIR__ . '/..';
$seederDir = $baseDir . '/src/Seeder';
$fixtureDir = $baseDir . '/src/Seeder/Fixtures';

// -----------------------------------------------------------------------------
// Helpers to build fixture arguments based on the entity's create() signature.
// -----------------------------------------------------------------------------

$enumFirstCase = [
    \App\Domain\Shared\Enums\PayrollRateType::class => 'LATE',
    \App\Domain\Shared\Enums\WorkPatternRuleType::class => 'REQUIRED_WEEKDAY',
    \App\Domain\Shared\Enums\ExcessTreatment::class => 'COUNT_AS_OVERTIME',
    \App\Domain\Shared\Enums\DeficitTreatment::class => 'MANUAL_REVIEW',
    \App\Domain\Shared\Enums\ReviewStatus::class => 1,
    \App\Domain\Shared\Enums\HolidayType::class => 'COMPANY_HOLIDAY',
];

$fallbackFromArray = [
    \App\Domain\AttendanceSummary\ValueObject\EmployeeSnapshot::class,
    \App\Domain\AttendanceSummary\ValueObject\AttendanceSummaryRef::class,
    \App\Domain\AttendanceSummary\ValueObject\WorkSchedule::class,
    \App\Domain\AttendanceSummary\ValueObject\AttendanceData::class,
    \App\Domain\AttendanceSummary\ValueObject\PermissionCollection::class,
    \App\Domain\AttendanceSummary\ValueObject\Summary::class,
    \App\Domain\AttendanceSummary\ValueObject\Policy::class,
    \App\Domain\AttendanceSummary\ValueObject\Notes::class,
    \App\Domain\AttendanceReview\ValueObject\EmployeeSnapshot::class,
    \App\Domain\AttendanceReview\ValueObject\AttendanceSummaryRef::class,
    \App\Domain\AttendanceReview\ValueObject\ReviewItemEvidence::class,
    \App\Domain\AttendanceReview\ValueObject\Notes::class,
    \App\Domain\PayrollReport\ValueObject\EmployeeSnapshot::class,
    \App\Domain\PayrollReport\ValueObject\AttendanceSummaryRef::class,
    \App\Domain\PayrollReport\ValueObject\PayrollSummary::class,
    \App\Domain\PayrollReport\ValueObject\OvertimeSummary::class,
    \App\Domain\PayrollReport\ValueObject\PayrollNet::class,
    \App\Domain\PayrollReport\ValueObject\Notes::class,
    \App\Domain\AttendancePolicies\ValueObject\PolicyDetail::class,
    \App\Domain\Employee\ValueObject\Image::class,
    \App\Domain\Employee\ValueObject\Contact::class,
    \App\Domain\AttendanceDevice\ValueObject\DeviceLocation::class,
];

function isNullAllowed(\ReflectionParameter $param): bool
{
    $type = $param->getType();
    if ($type instanceof \ReflectionNamedType) {
        return $type->allowsNull();
    }
    if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType) {
        return $type->allowsNull();
    }
    return false;
}

function toFakerArray(int $count = 1): array
{
    $items = [];
    for ($i = 0; $i < $count; $i++) {
        $items[] = [
            'employee_id' => '<numberBetween(1,10)>',
            'employee_name' => '<name()>',
            'info' => [],
            'device' => ['code' => '<word()>', 'name' => '<company()>'],
            'division' => ['id' => 1, 'code' => 'DIV', 'name' => 'Division'],
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'work_pattern' => null,
            'location' => null,
            'branch' => null,
            'attendance_policies' => null,
            'work_duration' => 480,
        ];
    }
    return $items;
}

function phpExpression(string $code): string
{
    return "<({$code})>";
}

function buildValueObjectExpression(string $typeName, string $entityClass, string $paramName): ?string
{
    // Special value objects that need realistic / non-empty data
    if ($typeName === \App\Domain\AttendanceCalendar\ValueObject\Scope::class) {
        return phpExpression('App\Domain\AttendanceCalendar\ValueObject\Scope::fromArray([["id" => 1, "code" => "HQ", "name" => "Headquarters"]])');
    }

    if ($typeName === \App\Domain\GeneralAffair\ValueObject\Asset::class) {
        return phpExpression('App\Domain\GeneralAffair\ValueObject\Asset::fromJson([])');
    }

    if ($typeName === \App\Domain\AttendanceShift\ValueObject\StartTime::class) {
        return phpExpression('App\Domain\AttendanceShift\ValueObject\StartTime::fromString("08:00:00")');
    }

    if ($typeName === \App\Domain\AttendanceShift\ValueObject\EndTime::class) {
        return phpExpression('App\Domain\AttendanceShift\ValueObject\EndTime::fromString("17:00:00")');
    }

    if ($typeName === \App\Domain\AttendanceShift\ValueObject\DefaultWorkTime::class) {
        return phpExpression('App\Domain\AttendanceShift\ValueObject\DefaultWorkTime::fromInt(480)');
    }

    if ($typeName === \App\Domain\AttendancePeriod\ValueObject\StartDate::class
        || $typeName === \App\Domain\AttendancePeriod\ValueObject\EndDate::class
        || $typeName === \App\Domain\AttendancePeriod\ValueObject\PayoutDate::class
    ) {
        return phpExpression($typeName . '::fromYearMonth(2024, 9)');
    }

    if ($typeName === \App\Domain\AttendanceSummary\ValueObject\Name::class) {
        return phpExpression('App\Domain\AttendanceSummary\ValueObject\Name::fromString(\Faker\Factory::create()->company())');
    }

    if ($typeName === \App\Domain\AttendanceDevice\ValueObject\IpAddress::class) {
        return phpExpression('App\Domain\AttendanceDevice\ValueObject\IpAddress::create(\Faker\Factory::create()->ipv4())');
    }

    if ($typeName === \App\Domain\AttendanceDevice\ValueObject\DeviceLocation::class) {
        return phpExpression('App\Domain\AttendanceDevice\ValueObject\DeviceLocation::fromArray(["lat" => -6.2, "lng" => 106.8])');
    }

    if (str_ends_with($typeName, 'Notes')) {
        return phpExpression($typeName . '::fromNullable(null)');
    }

    if (in_array($typeName, $GLOBALS['fallbackFromArray'], true)) {
        return phpExpression($typeName . '::fromArray([])');
    }

    if (enum_exists($typeName)) {
        $value = $GLOBALS['enumFirstCase'][$typeName] ?? null;
        if ($value === null) {
            $reflection = new \ReflectionEnum($typeName);
            $first = $reflection->getCases()[0] ?? null;
            $value = $first?->getValue();
            if ($value instanceof \BackedEnum) {
                $value = $value->value;
            } else {
                $value = $first?->getName();
            }
        }
        $literal = is_int($value) ? (string) $value : '"' . $value . '"';
        return phpExpression($typeName . '::from(' . $literal . ')');
    }

    $ref = new \ReflectionClass($typeName);

    if ($ref->hasMethod('fromArray')) {
        return phpExpression($typeName . '::fromArray([])');
    }

    if ($ref->hasMethod('fromJson')) {
        return phpExpression($typeName . '::fromJson(\"[]\")');
    }

    if ($ref->hasMethod('fromNullable')) {
        return phpExpression($typeName . '::fromNullable(null)');
    }

    if ($ref->hasMethod('fromString')) {
        return phpExpression($typeName . '::fromString(\"\")');
    }

    if ($ref->hasMethod('fromInt')) {
        return phpExpression($typeName . '::fromInt(0)');
    }

    if ($ref->hasMethod('create')) {
        return phpExpression($typeName . '::create()');
    }

    return null;
}

function resolveParamValue(\ReflectionParameter $param, string $entityClass, int $index): mixed
{
    $name = $param->getName();
    $type = $param->getType();
    $nullable = isNullAllowed($param);
    $optional = $param->isOptional();

    // 1. Nullable reference / optional value -> null for safer dummy data
    $referenceNames = [
        'attendancePoliciesId', 'workPatternId', 'branchType', 'employeeDebtId', 'payrollDeductionId',
        'payrollPeriod', 'approved', 'approvedNote',
        'period', 'notes', 'note', 'revision',
        'dateResign', 'settledAt',
    ];

    if ($nullable && in_array($name, $referenceNames, true)) {
        if ($name === 'note' && $optional === false) {
            return '<sentence()>';
        }
        if ($name === 'note' && str_contains($entityClass, 'AttendancePermission')) {
            return '<sentence()>';
        }
        return null;
    }

    // 2. Built-in shared value objects
    if ($type instanceof \ReflectionNamedType) {
        $typeName = $type->getName();

        if ($typeName === \App\Domain\Shared\ValueObject\ResourceStatus::class) {
            return '@status_active';
        }

        if ($typeName === \App\Domain\Shared\ValueObject\DetailInfo::class) {
            return '@info_empty';
        }

        if ($typeName === \App\Domain\Shared\ValueObject\SyncMdb::class) {
            return null;
        }

        // 3. Domain value objects and enums
        if (class_exists($typeName) || enum_exists($typeName)) {
            $expr = buildValueObjectExpression($typeName, $entityClass, $name);
            if ($expr !== null) {
                return $expr;
            }
        }

        // 4. array type
        if ($typeName === 'array') {
            if ($name === 'attendance') {
                return toFakerArray(1);
            }
            if ($name === 'leaveRecords' || $name === 'adjustmentRecords' || $name === 'holidayTypes') {
                return [];
            }
            return [];
        }
    }

    // 5. Scalars
    return scalarValue($name, $param, $nullable, $entityClass);
}

function scalarValue(string $name, \ReflectionParameter $param, bool $nullable, string $entityClass): mixed
{
    $type = $param->getType();
    $typeName = $type instanceof \ReflectionNamedType ? $type->getName() : 'mixed';

    if ($typeName === 'string') {
        return match (true) {
            $name === 'name' => '<company()>',
            $name === 'code' => '<word()>',
            $name === 'username' => '<userName()>',
            $name === 'email' => '<email()>',
            $name === 'address' => '<address()>',
            $name === 'note' || $name === 'notes' => '<sentence()>',
            $name === 'category' => '<word()>',
            $name === 'transactionType' => 'payment',
            $name === 'paymentSource' => 'cash',
            $name === 'leaveType' => 'annual',
            $name === 'holidayType' => 'COMPANY_HOLIDAY',
            $name === 'nik' => "<numerify('################')>",
            $name === 'date' || $name === 'startDate' || $name === 'endDate'
                || $name === 'periodStartDate' || $name === 'periodEndDate'
                || $name === 'birthDate' || $name === 'dateStart'
                || $name === 'settledAt' => "<date('Y-m-d')>",
            default => '<word()>',
        };
    }

    if ($typeName === 'int') {
        if ($name === 'period' && str_contains($entityClass, 'AttendancePeriod')) {
            return 202409;
        }

        $nonNullableInt = [
            'ssoId' => '<numberBetween(1,100000)>',
            'employeeId' => '<numberBetween(1,10)>',
            'deductionTypeId' => '<numberBetween(1,10)>',
            'employeeDebtId' => '<numberBetween(1,10)>',
            'productId' => '<numberBetween(1,10)>',
            'categoryId' => '<numberBetween(1,10)>',
            'locationId' => '<numberBetween(1,10)>',
            'permissionType' => '<numberBetween(1,5)>',
            'amount' => '<numberBetween(100000,5000000)>',
            'period' => '<numberBetween(202401,202412)>',
            'initialAmount' => '<numberBetween(1,20)>',
            'level' => '<numberBetween(1,10)>',
            'breakMinutes' => '<numberBetween(0,60)>',
            'crossDay' => 0,
            'requiresLocation' => '<numberBetween(0,1)>',
            'holidayWins' => 0,
            'substitutionAllowed' => 0,
            'dayOff' => 0,
            'createsDebt' => '<numberBetween(0,1)>',
            'usedAmount' => 0,
        ];

        if (isset($nonNullableInt[$name])) {
            return $nonNullableInt[$name];
        }

        if ($name === 'targetCount' && str_contains($entityClass, 'WorkPatternRule')) {
            return null;
        }

        return '<numberBetween(1,100)>';
    }

    if ($typeName === 'float') {
        return '<randomFloat(2,0,1000)>';
    }

    if ($typeName === 'bool') {
        return '<numberBetween(0,1)>';
    }

    if ($nullable) {
        return null;
    }

    return '<word()>';
}

function buildEntityYaml(string $entityClass, int $count = 10): string
{
    if (!class_exists($entityClass)) {
        return '';
    }

    $ref = new \ReflectionClass($entityClass);
    if (!$ref->hasMethod('create')) {
        return '';
    }

    $method = $ref->getMethod('create');
    $params = $method->getParameters();

    $shortName = (new \ReflectionClass($entityClass))->getShortName();
    $snakeName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $shortName));

    $entries = [];
    for ($i = 1; $i <= $count; $i++) {
        $args = [];
        foreach ($params as $idx => $param) {
            $args[] = resolveParamValue($param, $entityClass, $idx);
        }
        $entries[$snakeName . '_' . $i] = [
            '__factory' => [
                $entityClass . '::create' => $args,
            ],
        ];
    }

    $data = [
        \App\Domain\Shared\ValueObject\ResourceStatus::class => [
            'status_draft' => ['__factory' => [\App\Domain\Shared\ValueObject\ResourceStatus::class . '::from' => [2]]],
            'status_active' => ['__factory' => [\App\Domain\Shared\ValueObject\ResourceStatus::class . '::from' => [1]]],
        ],
        \App\Domain\Shared\ValueObject\DetailInfo::class => [
            'info_empty' => ['__factory' => [\App\Domain\Shared\ValueObject\DetailInfo::class . '::fromArray' => [[]]]],
        ],
        $entityClass => $entries,
    ];

    return "# 1. VALUE OBJECTS\n" . Yaml::dump($data, 12, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
}

function rebuildSeederClass(string $file, string $entityClass, string $yamlFile): void
{
    $short = (new \ReflectionClass($entityClass))->getShortName();
    $ns = (new \ReflectionClass($entityClass))->getNamespaceName();

    // Repository interface is Domain\X\Repository\XRepositoryInterface
    $domainParts = explode('\\', $ns);
    $module = $domainParts[2] ?? $short;
    $repositoryInterface = "App\\Domain\\{$module}\\Repository\\{$short}RepositoryInterface";

    $contents = <<<'PHP'
<?php

declare(strict_types=1);

namespace App\Seeder;

use {entityClass};
use {repositoryInterface};
use App\Infrastructure\Seeder\AbstractSeederData;
use App\Application\Shared\Factory\DetailInfoFactory;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Aliases\Aliases;

/**
 * Seeds {resource} table using Alice fixtures.
 */
final class Seed{short}Data extends AbstractSeederData
{
    private {short}RepositoryInterface $repository;

    protected const YAML_FILE = '{yamlFile}';
    protected const ENTITY_CLASS = {short}::class;

    public function __construct(
        ConnectionInterface $db,
        ClockInterface $clock,
        DetailInfoFactory $detailInfoFactory,
        Aliases $aliases,
        {short}RepositoryInterface $repository
    ) {
        parent::__construct($db, $clock, $detailInfoFactory, $aliases);
        $this->repository = $repository;
    }

    protected function insertEntity(object $entity, mixed $detailInfo): void
    {
        $entity->updateDetailInfo($detailInfo);
        $this->repository->insert($entity);
    }
}
PHP;

    $contents = str_replace(
        ['{entityClass}', '{repositoryInterface}', '{short}', '{yamlFile}', '{resource}'],
        [$entityClass, $repositoryInterface, $short, $yamlFile, $short],
        $contents
    );

    file_put_contents($file, $contents);
}

$GLOBALS['fallbackFromArray'] = $fallbackFromArray;
$GLOBALS['enumFirstCase'] = $enumFirstCase;

$seedFiles = glob($seederDir . '/Seed*Data.php');

foreach ($seedFiles as $seedFile) {
    $content = file_get_contents($seedFile);

    if (!preg_match("/protected\s+const\s+YAML_FILE\s*=\s*'([^']+)'/", $content, $yamlMatch)) {
        continue;
    }
    if (!preg_match("/protected\s+const\s+ENTITY_CLASS\s*=\s*([^;]+);/", $content, $entityMatch)) {
        continue;
    }

    $yamlFile = $yamlMatch[1];
    $entityClassRaw = trim($entityMatch[1]);

    $shortName = '';
    if (preg_match('/([A-Za-z0-9_]+)::class/', $entityClassRaw, $shortMatch)) {
        $shortName = $shortMatch[1];
    } else {
        $shortName = trim($entityClassRaw, "'\"");
    }

    $entityClass = null;
    if ($shortName !== '' && preg_match("/use\s+([A-Za-z0-9_\\\\]+\\\\{$shortName});/", $content, $useMatch)) {
        $entityClass = $useMatch[1];
    } else {
        $entityClass = $shortName;
    }

    if (!class_exists($entityClass)) {
        echo "⚠️ Skipping " . basename($seedFile) . " - entity class does not exist: {$entityClass}\n";
        continue;
    }

    $yaml = buildEntityYaml($entityClass);
    if ($yaml === '') {
        echo "⚠️ Skipping " . basename($seedFile) . " - no create() method for {$entityClass}\n";
        continue;
    }

    $fixturePath = $fixtureDir . '/' . $yamlFile;
    file_put_contents($fixturePath, $yaml);
    echo "📝 Generated fixture: {$fixturePath}\n";

    rebuildSeederClass($seedFile, $entityClass, $yamlFile);
    echo "🔧 Rebuilt seeder: {$seedFile}\n";
}

echo "\n✅ Done.\n";
