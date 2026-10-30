<?php
declare(strict_types=1);

final class AuthService
{
    public function __construct(
        private AuthRepository $repository
    ) {
    }

    // Xac thuc tai khoan nguoi dung
    public function authenticate(
        string $email,
        string $password
    ): ?array {
        $email = trim($email);

        // Kiem tra du lieu dau vao
        if ($email === '' || $password === '') {
            return null;
        }

        // Tim tai khoan trong MySQL
        $user = $this->repository->findByEmail($email);

        if ($user === null) {
            return null;
        }

        // Tu choi tai khoan bi vo hieu hoa
        if (!(bool) $user['is_active']) {
            return null;
        }

        // Kiem tra password hash
        if (!password_verify(
            $password,
            (string) $user['password_hash']
        )) {
            return null;
        }

        // Chi tra ve thong tin an toan
        return [
            'user_id' => (int) $user['user_id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role_id' => (int) $user['role_id'],
            'role_name' => $user['role_name']
        ];
    }
}
