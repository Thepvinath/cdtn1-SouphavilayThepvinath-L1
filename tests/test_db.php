<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config/Database.php';

try {
    $pdo = Database::connect();

    echo "MySQL connection: SUCCESS" . PHP_EOL;

    // Kiểm tra database hiện tại
    $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();

    echo "Database: " . $dbName . PHP_EOL;

    // Kiểm tra danh sách bảng
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    echo "Tables (" . count($tables) . "):" . PHP_EOL;

    foreach ($tables as $table) {
        echo "- " . $table . PHP_EOL;
    }

} catch (Throwable $e) {
    echo "Connection failed: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
