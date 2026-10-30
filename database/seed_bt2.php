<?php
declare(strict_types=1);

/**
 * Local BT2 demo seeder: dedicated smartcrm_bt2_* databases only.
 * Does not alter the original smartcrm database or store a public password.
 * Usage: php database/seed_bt2.php smartcrm_bt2_clean_clone
 */

$targetDb = $argv[1] ?? '';
if (!preg_match('/^smartcrm_bt2_[a-z0-9_]+$/D', $targetDb)) {
    fwrite(STDERR, "ERROR: Expected a dedicated smartcrm_bt2_* database.\n");
    exit(1);
}

try {
    $envPath = dirname(__DIR__) . '/.env';
    if (!is_file($envPath)) {
        throw new RuntimeException('Missing .env file. Copy .env.example first.');
    }
    $env = parse_ini_file($envPath, false, INI_SCANNER_RAW);
    if ($env === false) {
        throw new RuntimeException('Cannot parse .env');
    }
    if (($env['DB_NAME'] ?? '') !== $targetDb) {
        throw new RuntimeException('DB_NAME in .env must equal the target test database.');
    }
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $user = $env['DB_USER'] ?? '';
    $pass = $env['DB_PASSWORD'] ?? '';
    if ($user === '') {
        throw new RuntimeException('DB_USER is missing from .env');
    }

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$targetDb};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    if ($pdo->query('SELECT DATABASE()')->fetchColumn() !== $targetDb) {
        throw new RuntimeException('Connected to unexpected database');
    }

    $required = ['roles', 'users', 'customers', 'merge_operations', 'merge_items', 'schema_migrations'];
    $existing = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($required as $table) {
        if (!in_array($table, $existing, true)) {
            throw new RuntimeException("Missing table {$table}. Run database/migrate.php first.");
        }
    }
    $applied = $pdo->query("SELECT COUNT(*) FROM schema_migrations WHERE version = '001_initial'")->fetchColumn();
    if ((int) $applied !== 1) {
        throw new RuntimeException('Migration 001_initial is not recorded');
    }

    $pdo->beginTransaction();
    $pdo->exec("INSERT IGNORE INTO roles(role_name) VALUES ('SALES_STAFF'), ('STORE_MANAGER'), ('MARKETING')");

    // Demo-only customer records. Same normalized phone creates one duplicate pair.
    $records = [
        ['BT2 DEMO MASTER', '0888802026', '0888802026', 'bt2.master@example.com', 'Viet Nam'],
        ['BT2 DEMO SOURCE', '0888802026', '0888802026', 'bt2.source@example.com', 'Viet Nam'],
        ['BT2 DEMO NORMAL', '0888802027', '0888802027', 'bt2.normal@example.com', 'Viet Nam'],
    ];
    $addCustomer = $pdo->prepare(<<<'SQL'
INSERT INTO customers(full_name, phone, phone_normalized, email, address, status)
SELECT ?, ?, ?, ?, ?, 'ACTIVE'
WHERE NOT EXISTS (SELECT 1 FROM customers WHERE email = ?)
SQL
    );
    foreach ($records as $record) {
        $addCustomer->execute([...$record, $record[3]]);
    }

    $email = 'demo.sales@smartcrm.local';
    $findUser = $pdo->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
    $findUser->execute([$email]);
    $userExists = $findUser->fetchColumn();
    $newPassword = null;
    if (!$userExists) {
        $newPassword = bin2hex(random_bytes(9));
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        if ($hash === false) {
            throw new RuntimeException('Failed to hash local demo password');
        }
        $addUser = $pdo->prepare(<<<'SQL'
INSERT INTO users(role_id, full_name, email, password_hash, is_active)
SELECT role_id, 'Local Demo Sales', ?, ?, 1
FROM roles WHERE role_name = 'SALES_STAFF'
SQL
        );
        $addUser->execute([$email, $hash]);
        if ($addUser->rowCount() !== 1) {
            throw new RuntimeException('Could not create the local demo login');
        }
    }
    $managerEmail = 'demo.manager@smartcrm.local';
    $findUser->execute([$managerEmail]);
    $managerPassword = null;
    if (!$findUser->fetchColumn()) {
        $managerPassword = bin2hex(random_bytes(16));
        $managerHash = password_hash($managerPassword, PASSWORD_DEFAULT);
        $managerInsert = $pdo->prepare("INSERT INTO users(role_id, full_name, email, password_hash, is_active) SELECT role_id, 'Local Demo Manager', ?, ?, 1 FROM roles WHERE role_name='STORE_MANAGER'");
        $managerInsert->execute([$managerEmail, $managerHash]);
        if ($managerInsert->rowCount() !== 1) {
            throw new RuntimeException('Could not create demo manager');
        }
    }
    $pdo->commit();

    echo "Demo manager: {$managerEmail}\n";
    if ($managerPassword !== null) {
        echo "LOCAL MANAGER PASSWORD (shown only once): {$managerPassword}\n";
    } else {
        echo "SKIP: manager exists; password, role and active status unchanged.\n";
    }

    echo "SEED SUCCESS: {$targetDb}\n";
    echo "Demo user: {$email}\n";
    if ($newPassword !== null) {
        echo "LOCAL DEMO PASSWORD (shown only once): {$newPassword}\n";
        echo "Save it outside the repository. Do not commit a screenshot containing it.\n";
    } else {
        echo "SKIP: demo user already exists; its password was not changed.\n";
    }
    echo "Re-running the seeder will not duplicate these demo records.\n";
} catch (Throwable $e) {
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'SEED ERROR: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
