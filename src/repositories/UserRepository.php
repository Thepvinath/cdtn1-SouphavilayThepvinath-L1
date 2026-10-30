<?php
declare(strict_types=1);

final class UserRepository
{
    private const FIELDS = 'u.user_id, u.full_name, u.email, u.role_id, r.role_name, u.is_active';

    public function __construct(private PDO $pdo) {}

    public function all(): array
    {
        return $this->pdo->query('SELECT ' . self::FIELDS . ' FROM users u JOIN roles r ON r.role_id=u.role_id ORDER BY u.user_id')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT ' . self::FIELDS . ' FROM users u JOIN roles r ON r.role_id=u.role_id WHERE u.user_id=?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function transaction(callable $run): mixed
    {
        $this->pdo->beginTransaction();
        try {
            // A stable row serializes all user mutations, including concurrent demotions.
            $lock = $this->pdo->query("SELECT role_id FROM roles WHERE role_name='STORE_MANAGER' FOR UPDATE");
            if (!$lock->fetchColumn()) {
                throw new RuntimeException('Manager role is missing');
            }
            $result = $run();
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            $this->pdo->rollBack();
            if ($e instanceof PDOException && ($e->errorInfo[1] ?? null) === 1062) {
                throw new DomainException('Email already exists');
            }
            throw $e;
        }
    }

    public function emailExists(string $email, int $except = 0): bool
    {
        $stmt = $this->pdo->prepare('SELECT user_id FROM users WHERE email=? AND user_id<>?');
        $stmt->execute([$email, $except]);
        return (bool) $stmt->fetchColumn();
    }

    public function activeManagers(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON r.role_id=u.role_id WHERE u.is_active=1 AND r.role_name='STORE_MANAGER'")->fetchColumn();
    }

    public function save(array $data, ?int $id = null): int
    {
        $role = $this->pdo->prepare('SELECT role_id FROM roles WHERE role_name=?');
        $role->execute([$data['role_name']]);
        $roleId = $role->fetchColumn();
        if (!$roleId) {
            throw new InvalidArgumentException('Role is not configured');
        }
        $values = [$data['full_name'], $data['email'], $roleId];
        $hash = isset($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
        if ($id === null) {
            $sql = 'INSERT INTO users(full_name,email,role_id,password_hash) VALUES (?,?,?,?)';
            $values[] = $hash;
        } else {
            $sql = 'UPDATE users SET full_name=?,email=?,role_id=?';
            if ($hash !== null) {
                $sql .= ',password_hash=?';
                $values[] = $hash;
            }
            $sql .= ' WHERE user_id=?';
            $values[] = $id;
        }
        $this->pdo->prepare($sql)->execute($values);
        return $id ?? (int) $this->pdo->lastInsertId();
    }

    public function disable(int $id): void
    {
        $this->pdo->prepare('UPDATE users SET is_active=0 WHERE user_id=?')->execute([$id]);
    }
}
