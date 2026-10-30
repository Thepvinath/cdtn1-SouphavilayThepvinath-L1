<?php
declare(strict_types=1);

final class RequestGuard
{
    private static $userResolver = null;

    public static function setUserResolver(callable $resolver): void
    {
        self::$userResolver = $resolver;
    }

    public static function requireLogin(): array
    {
        $user = $_SESSION['user'] ?? null;
        if (is_array($user) && isset($user['user_id']) && self::$userResolver !== null) {
            $current = (self::$userResolver)((int) $user['user_id']);
            if (!$current || !(bool) $current['is_active']) {
                unset($_SESSION['user']);
                $user = null;
            } else {
                unset($current['is_active']);
                $_SESSION['user'] = $user = $current;
            }
        }

        if (!is_array($user) || !isset($user['user_id'])) {
            CustomerController::json(401, [
                'error' => 'UNAUTHORIZED',
                'message' => 'Vui long dang nhap'
            ]);
            exit;
        }

        return $user;
    }

    public static function requireRole(array $allowedRoles): array
    {
        $user = self::requireLogin();

        if (!in_array(
            $user['role_name'] ?? '',
            $allowedRoles,
            true
        )) {
            CustomerController::json(403, [
                'error' => 'FORBIDDEN',
                'message' => 'Ban khong co quyen thuc hien'
            ]);
            exit;
        }

        return $user;
    }

    public static function csrfToken(): string
    {
        if (
            !isset($_SESSION['csrf_token']) ||
            !is_string($_SESSION['csrf_token'])
        ) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(): void
    {
        $submitted = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (
            !is_string($submitted) ||
            !hash_equals(self::csrfToken(), $submitted)
        ) {
            CustomerController::json(403, [
                'error' => 'CSRF_INVALID',
                'message' => 'CSRF token khong hop le'
            ]);
            exit;
        }
    }
}
