<?php
declare(strict_types=1);

final class UserValidator
{
    public static function validate(array $input, bool $create = true): array
    {
        $allowed = ['full_name', 'email', 'role_name', 'password'];
        if (array_diff(array_keys($input), $allowed)) {
            throw new InvalidArgumentException('Unsupported user fields');
        }
        $data = [];
        foreach (['full_name', 'email', 'role_name'] as $field) {
            if (!isset($input[$field]) || !is_string($input[$field])) {
                throw new InvalidArgumentException("Invalid {$field}");
            }
            $data[$field] = trim($input[$field]);
        }
        if ($data['full_name'] === '' || strlen($data['full_name']) > 150) {
            throw new InvalidArgumentException('Full name is required (maximum 150 bytes)');
        }
        $data['email'] = strtolower($data['email']);
        if (strlen($data['email']) > 150 || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email');
        }
        if (!in_array($data['role_name'], ['STORE_MANAGER', 'SALES_STAFF', 'MARKETING'], true)) {
            throw new InvalidArgumentException('Invalid role');
        }
        if ($create || array_key_exists('password', $input)) {
            $password = $input['password'] ?? null;
            if (!is_string($password) || strlen($password) < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
                throw new InvalidArgumentException('Password must contain 12–72 bytes and no null bytes');
            }
            $data['password'] = $password;
        }
        return $data;
    }
}
