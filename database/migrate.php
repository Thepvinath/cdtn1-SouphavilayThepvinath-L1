<?php
declare(strict_types=1);

// ============================================
// SMART CRM - MEKONG MOBILE
// BT2 - Portable Migration Runner
// ============================================

// 1. Validate target database
$targetDb = $argv[1] ?? '';

// Only allow dedicated BT2 databases.
// Never migrate the original smartcrm database.
if (!preg_match('/^smartcrm_bt2_[a-z0-9_]+$/D', $targetDb)) {
    fwrite(
        STDERR,
        "ERROR: Database name must start with smartcrm_bt2_\n"
    );
    exit(1);
}

// 2. File paths
$envPath = dirname(__DIR__) . '/.env';
$migrationPath = __DIR__ . '/migrations/001_initial.sql';

try {
    // 3. Validate required files
    if (!is_file($envPath)) {
        throw new RuntimeException('Missing .env file');
    }

    if (!is_file($migrationPath)) {
        throw new RuntimeException('Missing migration file');
    }

    // 4. Read environment configuration
    $env = parse_ini_file(
        $envPath,
        false,
        INI_SCANNER_RAW
    );

    if ($env === false) {
        throw new RuntimeException('Cannot read .env');
    }

    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $user = $env['DB_USER'] ?? '';
    $pass = $env['DB_PASSWORD'] ?? '';

    if ($user === '') {
        throw new RuntimeException('DB_USER is missing');
    }

    // 5. Connect to target database
    // The database must already exist.
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$targetDb;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    // Verify actual database selection
    $connectedDb = $pdo->query(
        'SELECT DATABASE()'
    )->fetchColumn();

    if ($connectedDb !== $targetDb) {
        throw new RuntimeException(
            'Connected database does not match target'
        );
    }

    echo "Connected to: $targetDb" . PHP_EOL;

    // 6. Read migration
    $sql = file_get_contents($migrationPath);

    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException(
            'Cannot read migration or migration is empty'
        );
    }

    // Initial migration must not select another database.
    if (preg_match(
        '/\b(CREATE\s+DATABASE|DROP\s+DATABASE|USE\s+\w+)\b/i',
        $sql
    )) {
        throw new RuntimeException(
            'Migration must not create or switch databases'
        );
    }

    $checksum = hash('sha256', $sql);

    // 7. Check existing tables
    $tables = $pdo->query('SHOW TABLES')
        ->fetchAll(PDO::FETCH_COLUMN);

    $hasHistory = in_array(
        'schema_migrations',
        $tables,
        true
    );

    // Refuse to initialize migration history
    // in a non-empty unmanaged database.
    if (!$hasHistory && count($tables) > 0) {
        throw new RuntimeException(
            'Database is not empty. Migration refused.'
        );
    }

    // 8. Create migration history table
    if (!$hasHistory) {
        $pdo->exec("
            CREATE TABLE schema_migrations (
                version VARCHAR(100) PRIMARY KEY,
                checksum CHAR(64) NOT NULL,
                applied_at DATETIME NOT NULL
                    DEFAULT CURRENT_TIMESTAMP
            )
        ");
    }

    // 9. Check whether migration is applied
    $stmt = $pdo->prepare("
        SELECT checksum
        FROM schema_migrations
        WHERE version = ?
    ");

    $stmt->execute(['001_initial']);

    $existing = $stmt->fetch();

    $expectedTables = [
        'roles',
        'users',
        'customers',
        'merge_operations',
        'merge_items'
    ];

    if ($existing) {

        // Check migration checksum
        if (!hash_equals(
            $existing['checksum'],
            $checksum
        )) {
            throw new RuntimeException(
                'Migration file changed after application'
            );
        }

        // Verify expected tables
        $currentTables = $pdo->query('SHOW TABLES')
            ->fetchAll(PDO::FETCH_COLUMN);

        foreach ($expectedTables as $table) {
            if (!in_array($table, $currentTables, true)) {
                throw new RuntimeException(
                    "Missing expected table: $table"
                );
            }
        }

        echo "SKIP: 001_initial already applied."
            . PHP_EOL;

        exit(0);
    }

    // 10. Detect incomplete migration
    $currentTables = $pdo->query('SHOW TABLES')
        ->fetchAll(PDO::FETCH_COLUMN);

    if (
        count($currentTables) !== 1 ||
        !in_array(
            'schema_migrations',
            $currentTables,
            true
        )
    ) {
        throw new RuntimeException(
            'Possible incomplete migration. Manual review required.'
        );
    }

    // 11. Execute initial migration
    // This splitter supports the current simple
    // CREATE TABLE statements only.
    $statements = array_values(array_filter(
        array_map('trim', explode(';', $sql)),
        static fn(string $statement): bool =>
            $statement !== ''
    ));

    if (count($statements) !== 5) {
        throw new RuntimeException(
            'Expected exactly five SQL statements'
        );
    }

    // Confirm each statement is CREATE TABLE.
    foreach ($statements as $statement) {
        $withoutComments = preg_replace(
            '/^\s*(?:(?:--[^\r\n]*(?:\r?\n|$))|(?:#[^\r\n]*(?:\r?\n|$))|\s)+/',
            '',
            $statement
        );

        if (
            $withoutComments === null ||
            !preg_match(
                '/^CREATE\s+TABLE\s+/i',
                $withoutComments
            )
        ) {
            throw new RuntimeException(
                'Only CREATE TABLE statements are allowed'
            );
        }
    }

    $executed = 0;

    foreach ($statements as $statement) {
        $pdo->exec($statement);
        $executed++;
    }

    // 12. Record successful migration
    $stmt = $pdo->prepare("
        INSERT INTO schema_migrations (
            version,
            checksum
        )
        VALUES (?, ?)
    ");

    $stmt->execute([
        '001_initial',
        $checksum
    ]);

    echo "SUCCESS: 001_initial applied." . PHP_EOL;
    echo "Tables created: $executed" . PHP_EOL;

} catch (Throwable $e) {

    fwrite(
        STDERR,
        "MIGRATION ERROR: " . $e->getMessage() . PHP_EOL
    );

    exit(1);
}
