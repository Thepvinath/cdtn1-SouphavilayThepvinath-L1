<?php
declare(strict_types=1);

final class AuthRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    // Tim tai khoan theo email
    public function findByEmail(string $email): ?array
    {
        $sql = "
            SELECT
                u.user_id,
                u.role_id,
                u.full_name,
                u.email,
                u.password_hash,
                u.is_active,
                r.role_name
            FROM users u
            INNER JOIN roles r
                ON u.role_id = r.role_id
            WHERE u.email = :email
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            'email' => $email
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }
}
