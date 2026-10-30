<?php
declare(strict_types=1);

// Creates a randomly named, disposable BT2 database and a temporary app copy.
// Never changes the repository .env or connects to its configured database.
$root = dirname(__DIR__);
$target = 'smartcrm_bt2_users_' . bin2hex(random_bytes(6));
$runtime = sys_get_temp_dir() . '/smartcrm_users_' . bin2hex(random_bytes(6));
$process = null;
$pdo = null;
$createdDatabase = false;
$failed = 0;
$passed = 0;

function check(bool $ok, string $name): void
{
    global $failed, $passed;
    if (!$ok) { $failed++; echo "[FAIL] {$name}\n"; }
    else { $passed++; echo "[PASS] {$name}\n"; }
}

function api(CurlHandle $client, string $base, string $method, string $path, ?array $data = null, ?string $token = null): array
{
    curl_setopt_array($client, [
        CURLOPT_URL => $base . $path,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => $data === null ? null : json_encode($data),
        CURLOPT_HTTPHEADER => array_filter(['Content-Type: application/json', $token === null ? null : 'X-CSRF-Token: ' . $token]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $body = curl_exec($client);
    if ($body === false) throw new RuntimeException(curl_error($client));
    return [(int) curl_getinfo($client, CURLINFO_RESPONSE_CODE), json_decode($body, true), $body];
}

try {
    if (!extension_loaded('curl') || !extension_loaded('pdo_mysql')) throw new RuntimeException('Requires curl and pdo_mysql');
    $env = parse_ini_file($root . '/.env', false, INI_SCANNER_RAW);
    if (!$env) throw new RuntimeException('Missing or invalid .env');
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $env['DB_USER'], $env['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    if (!preg_match('/^smartcrm_bt2_users_[a-f0-9]{12}$/D', $target)) throw new RuntimeException('Unsafe target');
    $pdo->exec("CREATE DATABASE `{$target}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $createdDatabase = true;
    $pdo->exec("USE `{$target}`");
    $pdo->exec(file_get_contents($root . '/database/migrations/001_initial.sql'));
    $pdo->exec("INSERT INTO roles(role_name) VALUES ('STORE_MANAGER'),('SALES_STAFF'),('MARKETING')");
    $password = bin2hex(random_bytes(16));
    $insert = $pdo->prepare('INSERT INTO users(role_id,full_name,email,password_hash) VALUES (?,?,?,?)');
    $insert->execute([1, 'Test Manager', 'manager@example.com', password_hash($password, PASSWORD_DEFAULT)]);
    $managerId = (int) $pdo->lastInsertId();
    $insert->execute([2, 'Test Sales', 'sales@example.com', password_hash($password, PASSWORD_DEFAULT)]);
    $salesId = (int) $pdo->lastInsertId();

    mkdir($runtime . '/SmartCRM/sessions', 0700, true);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src', FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($files as $file) {
        $dest = $runtime . '/src/' . substr($file->getPathname(), strlen($root . '/src/'));
        if ($file->isDir()) { if (!is_dir($dest)) mkdir($dest, 0700, true); }
        else copy($file->getPathname(), $dest);
    }
    $config = file_get_contents($root . '/.env');
    $config = preg_replace('/^DB_NAME=.*$/m', 'DB_NAME=' . $target, $config);
    file_put_contents($runtime . '/.env', $config);
    $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
    if (!$socket) throw new RuntimeException('Cannot reserve test port');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $base = 'http://' . $address;
    $processEnv = getenv();
    $processEnv['LOCALAPPDATA'] = $runtime;
    $process = proc_open([PHP_BINARY, '-S', $address, '-t', $runtime . '/src/public', $runtime . '/src/public/index.php'], [0 => ['pipe', 'r'], 1 => ['file', $runtime . '/server.log', 'a'], 2 => ['file', $runtime . '/server.log', 'a']], $pipes, $runtime, $processEnv);
    if (!is_resource($process)) throw new RuntimeException('Cannot start PHP test server');
    fclose($pipes[0]);
    $manager = curl_init(); $sales = curl_init(); $anonymous = curl_init();
    foreach ([$manager, $sales, $anonymous] as $client) curl_setopt($client, CURLOPT_COOKIEFILE, '');
    $ready = false;
    for ($i = 0; $i < 50; $i++) {
        try { $ready = api($anonymous, $base, 'GET', '/api/auth/csrf')[0] === 200; } catch (RuntimeException $e) { /* Server starting. */ }
        if ($ready) break;
        usleep(100000);
    }
    if (!$ready) throw new RuntimeException('Test server did not become ready');
    foreach (['GET', 'POST'] as $method) check(api($anonymous, $base, $method, '/api/users')[0] === 401, "Anonymous collection {$method} rejected");
    foreach (['GET', 'PUT', 'DELETE'] as $method) check(api($anonymous, $base, $method, '/api/users/1')[0] === 401, "Anonymous detail {$method} rejected");
    $token = api($manager, $base, 'GET', '/api/auth/csrf')[1]['data']['csrf_token'];
    check(api($manager, $base, 'POST', '/api/auth/login', ['email' => 'manager@example.com', 'password' => $password], $token)[0] === 200, 'Manager login');
    $salesToken = api($sales, $base, 'GET', '/api/auth/csrf')[1]['data']['csrf_token'];
    check(api($sales, $base, 'POST', '/api/auth/login', ['email' => 'sales@example.com', 'password' => $password], $salesToken)[0] === 200, 'Sales login');
    foreach (['GET', 'POST'] as $method) check(api($sales, $base, $method, '/api/users', null, $salesToken)[0] === 403, "Sales collection {$method} forbidden");
    foreach (['GET', 'PUT', 'DELETE'] as $method) check(api($sales, $base, $method, '/api/users/1', null, $salesToken)[0] === 403, "Sales detail {$method} forbidden");
    $input = ['full_name' => 'Employee', 'email' => 'employee@example.com', 'role_name' => 'SALES_STAFF', 'password' => $password];
    check(api($manager, $base, 'POST', '/api/users', $input)[0] === 403, 'Create requires CSRF');
    check(api($manager, $base, 'PUT', '/api/users/1', $input)[0] === 403, 'Update requires CSRF');
    check(api($manager, $base, 'DELETE', '/api/users/1', null, 'invalid')[0] === 403, 'Disable requires valid CSRF');
    check(api($manager, $base, 'POST', '/api/users', array_replace($input, ['password' => 'short']), $token)[0] === 400, 'Invalid password rejected');
    [$status, $body, $raw] = api($manager, $base, 'POST', '/api/users', $input, $token);
    check($status === 201, 'Create user');
    $id = $body['data']['user_id'];
    check(!str_contains($raw, 'password') && !str_contains($raw, $password), 'Create does not disclose credentials');
    $hashQuery = $pdo->prepare('SELECT password_hash FROM users WHERE user_id=?');
    $hashQuery->execute([$id]);
    $originalHash = $hashQuery->fetchColumn();
    check(password_verify($password, $originalHash), 'Password stored as verifiable hash');
    check(api($manager, $base, 'POST', '/api/users', array_replace($input, ['email' => 'EMPLOYEE@example.com']), $token)[0] === 409, 'Duplicate normalized email rejected');
    [$status, $body, $raw] = api($manager, $base, 'GET', '/api/users');
    check($status === 200 && count($body['data']) === 3 && !str_contains($raw, 'password'), 'List safe users');
    check(api($manager, $base, 'GET', "/api/users/{$id}")[0] === 200, 'User detail');
    $update = $input; unset($update['password']); $update['full_name'] = 'Updated Employee';
    check(api($manager, $base, 'PUT', "/api/users/{$id}", $update, $token)[1]['data']['full_name'] === 'Updated Employee', 'Update user');
    $hashQuery->execute([$id]);
    check($hashQuery->fetchColumn() === $originalHash, 'Omitted password preserved');
    $update['password'] = bin2hex(random_bytes(16));
    check(api($manager, $base, 'PUT', "/api/users/{$id}", $update, $token)[0] === 200, 'Change password');
    $hashQuery->execute([$id]);
    check(password_verify($update['password'], $hashQuery->fetchColumn()), 'Updated password hash verifies');
    check(api($manager, $base, 'PUT', "/api/users/{$id}", array_replace($update, ['email' => 'sales@example.com']), $token)[0] === 409, 'Update duplicate rejected');
    check(api($manager, $base, 'DELETE', "/api/users/{$managerId}", null, $token)[0] === 409, 'Self disable rejected');
    $demotion = ['full_name' => 'Manager', 'email' => 'manager@example.com', 'role_name' => 'SALES_STAFF'];
    check(api($manager, $base, 'PUT', "/api/users/{$managerId}", $demotion, $token)[0] === 409, 'Last manager demotion rejected');
    check(api($manager, $base, 'DELETE', "/api/users/{$salesId}", null, $token)[0] === 200, 'Soft disable sales');
    check(api($sales, $base, 'GET', '/api/auth/me')[0] === 401, 'Disabled existing session revoked');
    check(api($sales, $base, 'POST', '/api/auth/login', ['email' => 'sales@example.com', 'password' => $password], $salesToken)[0] === 401, 'Disabled login rejected');
    check(api($manager, $base, 'DELETE', "/api/users/{$id}", null, $token)[0] === 200, 'Disable employee');
    check((int) api($manager, $base, 'GET', "/api/users/{$id}")[1]['data']['is_active'] === 0, 'Disabled user retained for detail');
    foreach (['GET', 'PUT', 'DELETE'] as $method) check(api($manager, $base, $method, '/api/users/999999', $method === 'PUT' ? $update : null, $token)[0] === 404, "Missing user {$method}");
    check(api($manager, $base, 'PATCH', '/api/users/1', null, $token)[0] === 405, 'Unsupported method rejected');
    $otherManager = api($manager, $base, 'POST', '/api/users', array_replace($input, ['email' => 'other.manager@example.com', 'role_name' => 'STORE_MANAGER']), $token);
    check($otherManager[0] === 201, 'Create second manager');
    $otherId = $otherManager[1]['data']['user_id'];
    $other = curl_init(); curl_setopt($other, CURLOPT_COOKIEFILE, '');
    $otherToken = api($other, $base, 'GET', '/api/auth/csrf')[1]['data']['csrf_token'];
    check(api($other, $base, 'POST', '/api/auth/login', ['email' => 'other.manager@example.com', 'password' => $password], $otherToken)[0] === 200, 'Second manager login');
    check(api($manager, $base, 'PUT', "/api/users/{$otherId}", array_replace($demotion, ['email' => 'other.manager@example.com']), $token)[0] === 200, 'Demote manager when another remains');
    check(api($other, $base, 'GET', '/api/users')[0] === 403, 'Demoted manager session loses access immediately');
    check(api($manager, $base, 'DELETE', "/api/users/{$otherId}", null, $token)[0] === 200, 'Disable demoted manager');

    // Regression smoke test: existing customer, duplicate and merge routes.
    $customer = ['full_name' => 'Regression Customer', 'phone' => '0888812345'];
    $created = api($manager, $base, 'POST', '/api/customers', $customer, $token);
    check($created[0] === 201, 'Customer create regression');
    $customerId = $created[1]['data']['customer_id'];
    check(api($manager, $base, 'GET', "/api/customers/{$customerId}")[0] === 200, 'Customer detail regression');
    check(api($manager, $base, 'PUT', "/api/customers/{$customerId}", ['full_name' => 'Changed Customer'], $token)[0] === 200, 'Customer update regression');
    check(api($manager, $base, 'GET', '/api/customers')[0] === 200, 'Customer list regression');
    check(api($manager, $base, 'GET', '/api/customers/duplicates')[0] === 200, 'Duplicate detection regression');
    check(api($manager, $base, 'POST', '/api/customers/merge', ['master_customer_id' => $customerId, 'source_customer_id' => $customerId], $token)[0] === 409, 'Merge guard regression');
    $pdo->prepare('INSERT INTO customers(full_name,phone,phone_normalized) VALUES (?,?,?)')->execute(['Duplicate Source', '0888812345', '0888812345']);
    $sourceId = (int) $pdo->lastInsertId();
    check(count(api($manager, $base, 'GET', '/api/customers/duplicates')[1]['data']) === 2, 'Duplicate pair regression');
    check(api($manager, $base, 'POST', '/api/customers/merge', ['master_customer_id' => $customerId, 'source_customer_id' => $sourceId], $token)[0] === 200, 'Successful merge regression');
    check((int) $pdo->query('SELECT COUNT(*) FROM merge_operations')->fetchColumn() === 1, 'Merge audit retained');
    check(api($manager, $base, 'DELETE', "/api/customers/{$customerId}", null, $token)[0] === 200, 'Customer disable regression');

    mkdir($runtime . '/database', 0700, true);
    copy($root . '/database/seed_bt2.php', $runtime . '/database/seed_bt2.php');
    $pdo->exec('CREATE TABLE schema_migrations(version VARCHAR(100) PRIMARY KEY)');
    $pdo->exec("INSERT INTO schema_migrations(version) VALUES ('001_initial')");
    $seed = function (string $db) use ($runtime): array {
        $proc = proc_open([PHP_BINARY, $runtime . '/database/seed_bt2.php', $db], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
        stream_get_contents($pipes[2]); fclose($pipes[2]);
        return [proc_close($proc), $output];
    };
    check($seed('smartcrm')[0] === 1, 'Seeder refuses original database');
    check($seed('smartcrm_bt2_mismatch')[0] === 1, 'Seeder refuses mismatched configuration');
    [$seedStatus, $seedOutput] = $seed($target);
    check($seedStatus === 0, 'BT2 demo seeder succeeds');
    $demo = $pdo->query("SELECT u.*,r.role_name FROM users u JOIN roles r ON r.role_id=u.role_id WHERE email='demo.manager@smartcrm.local'")->fetch(PDO::FETCH_ASSOC);
    preg_match('/LOCAL MANAGER PASSWORD \(shown only once\): ([a-f0-9]+)/', $seedOutput, $secret);
    check($demo && $demo['role_name'] === 'STORE_MANAGER' && isset($secret[1]) && password_verify($secret[1], $demo['password_hash']), 'Demo manager has generated hashed password');
    $before = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    [$seedStatus, $seedOutput] = $seed($target);
    check($seedStatus === 0 && !str_contains($seedOutput, 'PASSWORD (shown only once)') && $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() === $before, 'Seeder rerun preserves accounts and secrets');
} catch (Throwable $e) {
    $failed++;
    fwrite(STDERR, 'Integration error: ' . $e->getMessage() . PHP_EOL);
} finally {
    if (is_resource($process)) { proc_terminate($process); proc_close($process); }
    if ($pdo && $createdDatabase && preg_match('/^smartcrm_bt2_users_[a-f0-9]{12}$/D', $target)) {
        $pdo->exec("DROP DATABASE IF EXISTS `{$target}`");
    }
    if (is_dir($runtime)) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($runtime, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) { $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname()); }
        rmdir($runtime);
    }
}
echo "Integration: {$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
