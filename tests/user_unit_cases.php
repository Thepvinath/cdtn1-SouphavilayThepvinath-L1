<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/validators/UserValidator.php';
require_once __DIR__ . '/../src/services/UserService.php';

$validUser = ['full_name' => ' Test User ', 'email' => ' TEST@example.com ', 'role_name' => 'SALES_STAFF', 'password' => 'valid-test-password'];
testCase('User: normalization', function () use ($validUser): void {
    $data = UserValidator::validate($validUser);
    expectSame('Test User', $data['full_name']);
    expectSame('test@example.com', $data['email']);
});
foreach ([
    ['full_name', ''], ['full_name', []], ['full_name', str_repeat('a', 151)],
    ['email', 'invalid'], ['email', []], ['email', str_repeat('a', 145) . '@example.com'],
    ['role_name', 'ADMIN'], ['role_name', 1],
    ['password', 'short'], ['password', str_repeat('a', 73)], ['password', []], ['password', "abcdefghijkl\0"],
    ['is_active', false], ['password_hash', 'hash'],
] as [$field, $value]) {
    testCase("User: rejects invalid {$field} " . get_debug_type($value) . ' ' . strlen(serialize($value)), function () use ($validUser, $field, $value): void {
        expectException(InvalidArgumentException::class, fn () => UserValidator::validate(array_replace($validUser, [$field => $value])));
    });
}
testCase('User: password required on create', function () use ($validUser): void {
    unset($validUser['password']);
    expectException(InvalidArgumentException::class, fn () => UserValidator::validate($validUser));
});
testCase('User: optional password on update', function () use ($validUser): void {
    unset($validUser['password']);
    expectSame(false, array_key_exists('password', UserValidator::validate($validUser, false)));
});
testCase('User: reject last manager removal', function (): void {
    expectException(DomainException::class, fn () => UserService::guardManagerRemoval(['is_active' => 1, 'role_name' => 'STORE_MANAGER'], 1));
});
testCase('User: allow removal with another manager', function (): void {
    UserService::guardManagerRemoval(['is_active' => 1, 'role_name' => 'STORE_MANAGER'], 2);
    UserService::guardManagerRemoval(['is_active' => 0, 'role_name' => 'STORE_MANAGER'], 1);
    UserService::guardManagerRemoval(['is_active' => 1, 'role_name' => 'SALES_STAFF'], 1);
});
