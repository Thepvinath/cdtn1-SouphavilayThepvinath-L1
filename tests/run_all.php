<?php
declare(strict_types=1);

/**
 * Smart CRM - chạy TOÀN BỘ bộ kiểm thử bằng một lệnh.
 *
 * Từ thư mục gốc dự án:
 *   & "C:\xampp\php\php.exe" tests/run_all.php
 *
 * Thứ tự chạy:
 *   1. tests/run_unit_tests.php       - unit test, không cần CSDL
 *   2. tests/run_user_integration.php - HTTP integration, tự tạo rồi xoá
 *                                       database tạm smartcrm_bt2_users_*
 *
 * Mỗi bộ chạy trong một tiến trình PHP riêng, dùng đúng tệp PHP đang chạy
 * script này (PHP_BINARY), nên không phụ thuộc đường dẫn cài đặt PHP.
 * Mã thoát: 0 khi mọi bộ PASS, 1 khi có bất kỳ bộ nào FAIL.
 */

$root = dirname(__DIR__);

$suites = [
    'Unit tests' => 'tests/run_unit_tests.php',
    'HTTP integration tests' => 'tests/run_user_integration.php',
    'BT3 test cases (TC-01..TC-12)' => 'tests/run_bt3_cases.php',
];

$results = [];

foreach ($suites as $name => $script) {
    echo '=== ' . $name . ' (' . $script . ')' . PHP_EOL;

    $process = proc_open(
        [PHP_BINARY, $root . '/' . $script],
        [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR],
        $pipes,
        $root
    );

    if (!is_resource($process)) {
        echo '[FAIL] Khong khoi dong duoc ' . $script . PHP_EOL;
        $results[$name] = 1;
        continue;
    }

    fclose($pipes[0]);
    $results[$name] = proc_close($process);
    echo PHP_EOL;
}

echo '=== Tong ket' . PHP_EOL;

$failedSuites = 0;

foreach ($results as $name => $exitCode) {
    $ok = $exitCode === 0;
    if (!$ok) {
        $failedSuites++;
    }
    echo ($ok ? '[PASS] ' : '[FAIL] ') . $name . ' (exit code ' . $exitCode . ')' . PHP_EOL;
}

echo PHP_EOL;
echo $failedSuites === 0
    ? 'ALL TEST SUITES PASSED' . PHP_EOL
    : $failedSuites . ' TEST SUITE(S) FAILED' . PHP_EOL;

exit($failedSuites === 0 ? 0 : 1);
