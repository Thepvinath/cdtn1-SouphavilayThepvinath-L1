<?php
declare(strict_types=1);

/**
 * Smart CRM native PHP unit tests.
 * Run from repository root:
 *   & "C:\xampp\php\php.exe" tests/run_unit_tests.php
 *
 * Tests only validation and pre-transaction merge guards.
 * No database connection or modifications are made.
 */
require_once __DIR__ . '/../src/validators/CustomerValidator.php';
require_once __DIR__ . '/../src/services/MergeService.php';

$passed = 0;
$failed = 0;

function testCase(string $name, callable $run): void
{
    global $passed, $failed;

    try {
        $run();
        $passed++;
        echo "[PASS] {$name}" . PHP_EOL;
    } catch (Throwable $e) {
        $failed++;
        echo "[FAIL] {$name}: " . $e->getMessage() . PHP_EOL;
    }
}

function expectSame(mixed $expected, mixed $actual): void
{
    if ($expected !== $actual) {
        throw new RuntimeException(
            'Expected ' . var_export($expected, true) .
            ', got ' . var_export($actual, true)
        );
    }
}

function expectException(string $expectedClass, callable $run): void
{
    try {
        $run();
    } catch (Throwable $e) {
        if (!$e instanceof $expectedClass) {
            throw new RuntimeException(
                "Expected {$expectedClass}, got " . get_class($e) .
                ': ' . $e->getMessage(),
                0,
                $e
            );
        }
        return;
    }

    throw new RuntimeException("Expected {$expectedClass}, but no exception was thrown");
}

// This PDO test double cannot connect to or write to a database.
final class NoDatabasePDO extends PDO
{
    public function __construct()
    {
        // Deliberately do not call PDO::__construct().
    }

    public function beginTransaction(): bool
    {
        throw new RuntimeException('Unexpected database access in a unit test');
    }
}

$mergeService = new MergeService(new NoDatabasePDO());

// Customer create validation

testCase('UT01 - Create: missing full name', function (): void {
    expectException(InvalidArgumentException::class, function (): void {
        CustomerValidator::validateCreate(['phone' => '0888801025']);
    });
});

testCase('UT02 - Create: missing phone', function (): void {
    expectException(InvalidArgumentException::class, function (): void {
        CustomerValidator::validateCreate(['full_name' => 'Test Customer']);
    });
});

testCase('UT03 - Create: phone contains letters', function (): void {
    expectException(InvalidArgumentException::class, function (): void {
        CustomerValidator::validateCreate([
            'full_name' => 'Test Customer',
            'phone' => '08888abc25',
        ]);
    });
});

testCase('UT04 - Create: fewer than 8 phone digits', function (): void {
    expectException(InvalidArgumentException::class, function (): void {
        CustomerValidator::validateCreate([
            'full_name' => 'Test Customer',
            'phone' => '1234567',
        ]);
    });
});

testCase('UT05 - Create: more than 15 phone digits', function (): void {
    expectException(InvalidArgumentException::class, function (): void {
        CustomerValidator::validateCreate([
            'full_name' => 'Test Customer',
            'phone' => '1234567890123456',
        ]);
    });
});

testCase('UT06 - Create: invalid email', function (): void {
    expectException(InvalidArgumentException::class, function (): void {
        CustomerValidator::validateCreate([
            'full_name' => 'Test Customer',
            'phone' => '0888801025',
            'email' => 'not-an-email',
        ]);
    });
});

testCase('UT07 - Create: valid data is trimmed', function (): void {
    $result = CustomerValidator::validateCreate([
        'full_name' => '  Test Customer  ',
        'phone' => ' 0888801025 ',
        'email' => ' test@example.com ',
        'address' => '  Vientiane  ',
    ]);

    expectSame('Test Customer', $result['full_name']);
    expectSame('0888801025', $result['phone']);
    expectSame('test@example.com', $result['email']);
    expectSame('Vientiane', $result['address']);
});

testCase('UT08 - Create: optional fields become null', function (): void {
    $result = CustomerValidator::validateCreate([
        'full_name' => 'Test Customer',
        'phone' => '+856 (20) 1234-5678',
    ]);

    expectSame(null, $result['email']);
    expectSame(null, $result['address']);
    expectSame('+856 (20) 1234-5678', $result['phone']);
});

// Customer update validation

testCase('UT09 - Update: missing full name', function (): void {
    expectException(InvalidArgumentException::class, function (): void {
        CustomerValidator::validateUpdate(['email' => 'test@example.com']);
    });
});

testCase('UT10 - Update: invalid email', function (): void {
    expectException(InvalidArgumentException::class, function (): void {
        CustomerValidator::validateUpdate([
            'full_name' => 'Test Customer',
            'email' => 'invalid-email',
        ]);
    });
});

testCase('UT11 - Update: valid data and empty optional fields', function (): void {
    $result = CustomerValidator::validateUpdate([
        'full_name' => '  Updated Customer  ',
        'email' => ' ',
        'address' => '  ',
    ]);

    expectSame('Updated Customer', $result['full_name']);
    expectSame(null, $result['email']);
    expectSame(null, $result['address']);
});

// MergeService input guards (executed before beginTransaction())

testCase('UT12 - Merge: invalid master ID', function () use ($mergeService): void {
    expectException(InvalidArgumentException::class, function () use ($mergeService): void {
        $mergeService->merge(0, 14, 1);
    });
});

testCase('UT13 - Merge: invalid source ID', function () use ($mergeService): void {
    expectException(InvalidArgumentException::class, function () use ($mergeService): void {
        $mergeService->merge(13, -1, 1);
    });
});

testCase('UT14 - Merge: invalid performer ID', function () use ($mergeService): void {
    expectException(InvalidArgumentException::class, function () use ($mergeService): void {
        $mergeService->merge(13, 14, 0);
    });
});

testCase('UT15 - Merge: self-merge is rejected', function () use ($mergeService): void {
    expectException(DomainException::class, function () use ($mergeService): void {
        $mergeService->merge(13, 13, 1);
    });
});

require __DIR__ . '/user_unit_cases.php';

echo PHP_EOL;
echo "Results: {$passed} passed, {$failed} failed, " . ($passed + $failed) . " total" . PHP_EOL;
exit($failed === 0 ? 0 : 1);
