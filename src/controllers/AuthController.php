<?php
declare(strict_types=1);

final class AuthController
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    // POST /api/auth/login
    public function login(): void
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($input)) {
            CustomerController::json(400, [
                'error' => 'VALIDATION_ERROR',
                'message' => 'Du lieu JSON khong hop le'
            ]);
            return;
        }

        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (!is_string($email) || !is_string($password)) {
            CustomerController::json(400, [
                'error' => 'VALIDATION_ERROR',
                'message' => 'Email va mat khau khong hop le'
            ]);
            return;
        }

        $user = $this->authService->authenticate(
            $email,
            $password
        );

        if ($user === null) {
            CustomerController::json(401, [
                'error' => 'UNAUTHORIZED',
                'message' => 'Email hoac mat khau khong dung'
            ]);
            return;
        }

        session_regenerate_id(true);

        $_SESSION['user'] = $user;

        CustomerController::json(200, [
            'message' => 'Dang nhap thanh cong',
            'data' => $user
        ]);
    }

    // GET /api/auth/me
    public function me(): void
    {
        RequestGuard::requireLogin();
        if (!isset($_SESSION['user'])) {
            CustomerController::json(401, [
                'error' => 'UNAUTHORIZED',
                'message' => 'Chua dang nhap'
            ]);
            return;
        }

        CustomerController::json(200, [
            'data' => $_SESSION['user']
        ]);
    }

    // POST /api/auth/logout
    public function logout(): void
    {
        unset($_SESSION['user']);

        session_regenerate_id(true);

        CustomerController::json(200, [
            'message' => 'Dang xuat thanh cong'
        ]);
    }
}
