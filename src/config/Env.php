<?php
declare(strict_types=1);

/**
 * Đọc cấu hình ứng dụng (BUG-06).
 *
 * - Có tệp .env ở thư mục gốc dự án  -> dùng tệp đó (máy Windows/XAMPP theo README,
 *   và bản sao tạm do bộ kiểm thử tự ghi).
 * - Không có tệp .env                -> đọc biến môi trường của tiến trình
 *   (container Docker: docker-compose truyền biến qua env_file/environment).
 *
 * Dùng chung cho src/config/Database.php, database/migrate.php,
 * database/seed_bt2.php và tests/.
 */
final class Env
{
    /** Các biến ứng dụng sử dụng. */
    public const KEYS = [
        'DB_HOST',
        'DB_PORT',
        'DB_NAME',
        'DB_USER',
        'DB_PASSWORD',
        'APP_ENV',
        'SESSION_SAVE_PATH',
        'DEMO_SALES_PASSWORD',
        'DEMO_MANAGER_PASSWORD',
    ];

    public static function load(?string $envPath = null): array
    {
        $envPath ??= dirname(__DIR__, 2) . '/.env';

        if (is_file($envPath)) {
            $values = parse_ini_file($envPath, false, INI_SCANNER_RAW);

            if ($values === false) {
                throw new RuntimeException('Khong doc duoc file .env');
            }

            return $values;
        }

        $values = [];

        foreach (self::KEYS as $key) {
            $value = getenv($key);

            if ($value !== false) {
                $values[$key] = $value;
            }
        }

        if (!isset($values['DB_NAME'], $values['DB_USER'])) {
            throw new RuntimeException(
                'Thieu cau hinh CSDL: tao file .env tu .env.example hoac dat bien moi truong DB_*'
            );
        }

        return $values;
    }

    /** Ghi cấu hình ra dạng nội dung tệp .env (dùng cho bản sao tạm của bộ kiểm thử). */
    public static function toDotenv(array $values): string
    {
        $lines = [];

        foreach ($values as $key => $value) {
            $lines[] = $key . '=' . $value;
        }

        return implode("\n", $lines) . "\n";
    }
}
