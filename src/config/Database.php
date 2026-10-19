<?php
declare(strict_types=1);

final class Database
{
    public static function connect(): PDO
    {
        // Đọc cấu hình từ file .env
        $envPath = dirname(__DIR__, 2) . '/.env';

        if (!file_exists($envPath)) {
            throw new RuntimeException('Khong tim thay file .env');
        }

        $env = parse_ini_file(
            $envPath,
            false,
            INI_SCANNER_RAW
        );

        if ($env === false) {
            throw new RuntimeException('Khong doc duoc file .env');
        }

        $host = $env['DB_HOST'] ?? '127.0.0.1';
        $port = $env['DB_PORT'] ?? '3306';
        $name = $env['DB_NAME'] ?? 'smartcrm';
        $user = $env['DB_USER'] ?? '';
        $pass = $env['DB_PASSWORD'] ?? '';

        $dsn = "mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);
    }
}
