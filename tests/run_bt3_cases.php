<?php
declare(strict_types=1);

/**
 * Smart CRM - 12 test case cua BT3 (xem docs/test-plan.md va docs/test-report.md).
 *
 * Tu thu muc goc du an:
 *   & "C:\xampp\php\php.exe" tests/run_bt3_cases.php          # chay ca 12 test case
 *   & "C:\xampp\php\php.exe" tests/run_bt3_cases.php TC-05    # chay mot test case
 *   & "C:\xampp\php\php.exe" tests/run_bt3_cases.php TC-01 TC-07
 *
 * - Test case Unit (TC-04, 05, 06, 08, 09) khong ket noi CSDL: dung doi tuong gia.
 * - Test case API va Tai lap (TC-01, 02, 03, 07, 10, 11, 12) tu tao database tam
 *   smartcrm_bt2_tc_<ngau nhien>, chay migration + seeder THAT cua repo, khoi dong
 *   PHP server tam, va XOA database tam khi ket thuc. Khong doc/sua database trong .env.
 * - Ma thoat: 0 khi moi test case duoc chon PASS, 1 khi co test case FAIL.
 */

$root = dirname(__DIR__);

require_once $root . '/src/repositories/CustomerRepository.php';
require_once $root . '/src/validators/CustomerValidator.php';
require_once $root . '/src/services/CustomerService.php';
require_once $root . '/src/services/MergeService.php';

// ---------------------------------------------------------------
// Doi tuong gia cho test Unit
// ---------------------------------------------------------------

/** Repository gia: luu trong bo nho, dem so lan create(). */
final class FakeCustomerRepository extends CustomerRepository
{
    public array $rows = [];
    public int $createCalls = 0;

    public function __construct()
    {
        // Co y khong goi parent::__construct(): khong co PDO, khong co CSDL.
    }

    public function findAll(string $keyword = ''): array
    {
        return array_values($this->rows);
    }

    public function findById(int $id): ?array
    {
        return $this->rows[$id] ?? null;
    }

    public function findByPhone(string $phone): ?array
    {
        foreach ($this->rows as $row) {
            if ($row['phone_normalized'] === $phone && $row['status'] === 'ACTIVE') {
                return $row;
            }
        }
        return null;
    }

    public function create(array $data): int
    {
        $this->createCalls++;
        $id = count($this->rows) + 1;
        $this->rows[$id] = $data + ['customer_id' => $id, 'status' => 'ACTIVE'];
        return $id;
    }
}

/** PDO gia: moi truy cap CSDL deu nem loi. */
final class NoDatabasePDOForCases extends PDO
{
    public function __construct()
    {
    }

    public function beginTransaction(): bool
    {
        throw new RuntimeException('Unexpected database access');
    }
}

/** Chay $run, tra ve [ten lop ngoai le, thong bao] hoac [null, null] neu khong nem. */
function catchException(callable $run): array
{
    try {
        $run();
    } catch (Throwable $e) {
        return [get_class($e), $e->getMessage()];
    }
    return [null, null];
}

// ---------------------------------------------------------------
// Moi truong API tam (chi dung khi chon test case API)
// ---------------------------------------------------------------

final class ApiEnv
{
    public PDO $pdo;
    public string $base;
    public string $target;
    public string $runtime;
    public CurlHandle $sales;
    public CurlHandle $anonymous;
    public string $salesToken;
    /** @var resource|null */
    public $process = null;
    public bool $createdDatabase = false;

    public function call(CurlHandle $client, string $method, string $path, ?array $data = null, ?string $token = null): array
    {
        curl_setopt_array($client, [
            CURLOPT_URL => $this->base . $path,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $data === null ? null : json_encode($data),
            CURLOPT_HTTPHEADER => array_values(array_filter([
                'Content-Type: application/json',
                $token === null ? null : 'X-CSRF-Token: ' . $token,
            ])),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $raw = curl_exec($client);
        if ($raw === false) {
            throw new RuntimeException(curl_error($client));
        }
        return [(int) curl_getinfo($client, CURLINFO_RESPONSE_CODE), json_decode($raw, true), $raw];
    }

    public function count(string $table, string $where = '1=1'): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM `{$table}` WHERE {$where}")->fetchColumn();
    }

    /** Chay mot script PHP cua repo ban sao trong runtime. */
    public function runScript(string $relative, string $arg): array
    {
        $proc = proc_open(
            [PHP_BINARY, $this->runtime . '/' . $relative, $arg],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $this->runtime
        );
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        return [proc_close($proc), $out . $err];
    }
}

function copyTree(string $from, string $to): void
{
    if (!is_dir($to)) {
        mkdir($to, 0700, true);
    }
    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($items as $item) {
        $dest = $to . '/' . substr($item->getPathname(), strlen($from) + 1);
        if ($item->isDir()) {
            if (!is_dir($dest)) {
                mkdir($dest, 0700, true);
            }
        } else {
            copy($item->getPathname(), $dest);
        }
    }
}

function setUpApiEnv(string $root): ApiEnv
{
    if (!extension_loaded('curl') || !extension_loaded('pdo_mysql')) {
        throw new RuntimeException('Can PHP extension curl va pdo_mysql');
    }
    require_once $root . '/src/config/Env.php';
    $env = Env::load($root . '/.env'); // tep .env hoac bien moi truong DB_* (BUG-06)

    $e = new ApiEnv();
    $e->target = 'smartcrm_bt2_tc_' . bin2hex(random_bytes(6));
    $e->runtime = sys_get_temp_dir() . '/smartcrm_tc_' . bin2hex(random_bytes(6));

    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $port = $env['DB_PORT'] ?? '3306';
    $e->pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $env['DB_USER'] ?? '',
        $env['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $e->pdo->exec("CREATE DATABASE `{$e->target}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $e->createdDatabase = true;
    $e->pdo->exec("USE `{$e->target}`");

    // Ban sao tam cua src/ va database/, voi .env tro vao database tam.
    mkdir($e->runtime . '/SmartCRM/sessions', 0700, true);
    copyTree($root . '/src', $e->runtime . '/src');
    copyTree($root . '/database', $e->runtime . '/database');
    file_put_contents($e->runtime . '/.env', Env::toDotenv(array_intersect_key(
        ['DB_NAME' => $e->target] + $env,
        array_flip(['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASSWORD'])
    )));

    // Dung CSDL bang migration va seeder THAT cua repo (lan chay thu nhat).
    [$status, $out] = $e->runScript('database/migrate.php', $e->target);
    if ($status !== 0) {
        throw new RuntimeException("Migration lan 1 that bai:\n" . $out);
    }
    [$status, $seedOut] = $e->runScript('database/seed_bt2.php', $e->target);
    if ($status !== 0) {
        throw new RuntimeException("Seeder lan 1 that bai:\n" . $seedOut);
    }
    if (!preg_match('/LOCAL DEMO PASSWORD \(shown only once\): (\S+)/', $seedOut, $salesSecret)) {
        throw new RuntimeException('Seeder khong tao mat khau tai khoan demo Sales');
    }

    // Khoi dong PHP server tam.
    $socket = stream_socket_server('tcp://127.0.0.1:0');
    $address = stream_socket_get_name($socket, false);
    fclose($socket);
    $e->base = 'http://' . $address;
    $processEnv = getenv();
    $processEnv['LOCALAPPDATA'] = $e->runtime;
    $e->process = proc_open(
        [PHP_BINARY, '-S', $address, '-t', $e->runtime . '/src/public', $e->runtime . '/src/public/index.php'],
        [0 => ['pipe', 'r'], 1 => ['file', $e->runtime . '/server.log', 'a'], 2 => ['file', $e->runtime . '/server.log', 'a']],
        $pipes,
        $e->runtime,
        $processEnv
    );
    fclose($pipes[0]);

    $e->sales = curl_init();
    $e->anonymous = curl_init();
    curl_setopt($e->sales, CURLOPT_COOKIEFILE, '');
    curl_setopt($e->anonymous, CURLOPT_COOKIEFILE, '');

    $ready = false;
    for ($i = 0; $i < 50 && !$ready; $i++) {
        try {
            $ready = $e->call($e->anonymous, 'GET', '/api/auth/csrf')[0] === 200;
        } catch (RuntimeException $ignored) {
        }
        if (!$ready) {
            usleep(100000);
        }
    }
    if (!$ready) {
        throw new RuntimeException('PHP server tam khong khoi dong duoc');
    }

    // Dang nhap tai khoan demo SALES_STAFF do seeder tao.
    $token = $e->call($e->sales, 'GET', '/api/auth/csrf')[1]['data']['csrf_token'];
    $login = $e->call($e->sales, 'POST', '/api/auth/login', [
        'email' => 'demo.sales@smartcrm.local',
        'password' => $salesSecret[1],
    ], $token);
    if ($login[0] !== 200) {
        throw new RuntimeException('Dang nhap demo.sales that bai: HTTP ' . $login[0]);
    }
    $e->salesToken = $e->call($e->sales, 'GET', '/api/auth/csrf')[1]['data']['csrf_token'];

    return $e;
}

function tearDownApiEnv(?ApiEnv $e): void
{
    if ($e === null) {
        return;
    }
    if (is_resource($e->process)) {
        proc_terminate($e->process);
        proc_close($e->process);
    }
    if ($e->createdDatabase && preg_match('/^smartcrm_bt2_tc_[a-f0-9]{12}$/D', $e->target)) {
        $e->pdo->exec("DROP DATABASE IF EXISTS `{$e->target}`");
    }
    if (is_dir($e->runtime)) {
        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($e->runtime, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($e->runtime);
    }
}

// ---------------------------------------------------------------
// 12 test case
// Moi test case tra ve [bool pass, string ket qua thuc te].
// ---------------------------------------------------------------

$cases = [
    'TC-01' => [
        'type' => 'Luong dung', 'priority' => 'Cao', 'api' => true,
        'pre' => 'Da dang nhap SALES_STAFF; SDT 0901000001 chua ton tai',
        'data' => 'POST /api/customers {full_name:"TC01 Nguyen Van Moi", phone:"0901000001", email:"tc01@example.com", address:"Quan 1"}',
        'expect' => 'HTTP 201, message "Tao ho so khach hang thanh cong"; bang customers +1 dong, phone_normalized = 0901000001',
        'run' => function (ApiEnv $e): array {
            $before = $e->count('customers');
            [$status, $body] = $e->call($e->sales, 'POST', '/api/customers', [
                'full_name' => 'TC01 Nguyen Van Moi', 'phone' => '0901000001',
                'email' => 'tc01@example.com', 'address' => 'Quan 1',
            ], $e->salesToken);
            $after = $e->count('customers');
            $stored = $e->count('customers', "phone_normalized = '0901000001'");
            $message = $body['message'] ?? '';
            $pass = $status === 201 && $message === 'Tao ho so khach hang thanh cong'
                && $after === $before + 1 && $stored === 1;
            return [$pass, "HTTP {$status}, message \"{$message}\"; customers {$before} -> {$after}; dong co phone_normalized=0901000001: {$stored}"];
        },
    ],
    'TC-02' => [
        'type' => 'Luong dung', 'priority' => 'Cao', 'api' => true,
        'pre' => 'Da dang nhap SALES_STAFF; da tao "TC02 Tran Thi Tim", SDT 0901000002',
        'data' => 'GET /api/customers?search=TC02%20Tran',
        'expect' => 'HTTP 200; dung 1 ket qua full_name "TC02 Tran Thi Tim"; SDT bi che "09******02"',
        'run' => function (ApiEnv $e): array {
            $e->call($e->sales, 'POST', '/api/customers', ['full_name' => 'TC02 Tran Thi Tim', 'phone' => '0901000002'], $e->salesToken);
            [$status, $body] = $e->call($e->sales, 'GET', '/api/customers?search=' . rawurlencode('TC02 Tran'));
            $data = $body['data'] ?? [];
            $name = $data[0]['full_name'] ?? '-';
            $phone = $data[0]['phone'] ?? '-';
            $pass = $status === 200 && count($data) === 1 && $name === 'TC02 Tran Thi Tim' && $phone === '09******02';
            return [$pass, "HTTP {$status}; " . count($data) . " ket qua; full_name \"{$name}\"; phone \"{$phone}\""];
        },
    ],
    'TC-03' => [
        'type' => 'Luong dung', 'priority' => 'Cao', 'api' => true,
        'pre' => 'Da dang nhap SALES_STAFF; 2 ho so ACTIVE "TC03 MASTER" va "TC03 SOURCE" cung SDT 0901000003',
        'data' => 'POST /api/customers/merge {master_customer_id: <MASTER>, source_customer_id: <SOURCE>}',
        'expect' => 'HTTP 200, data.status SUCCESS; SOURCE -> MERGED va deleted_at co gia tri; MASTER van ACTIVE; merge_operations +1; merge_items co SOURCE',
        'run' => function (ApiEnv $e): array {
            $insert = $e->pdo->prepare('INSERT INTO customers(full_name, phone, phone_normalized) VALUES (?, ?, ?)');
            $insert->execute(['TC03 MASTER', '0901000003', '0901000003']);
            $master = (int) $e->pdo->lastInsertId();
            $insert->execute(['TC03 SOURCE', '0901000003', '0901000003']);
            $source = (int) $e->pdo->lastInsertId();
            $opsBefore = $e->count('merge_operations');
            [$status, $body] = $e->call($e->sales, 'POST', '/api/customers/merge', [
                'master_customer_id' => $master, 'source_customer_id' => $source,
            ], $e->salesToken);
            $rows = $e->pdo->query("SELECT customer_id, status, deleted_at FROM customers WHERE customer_id IN ({$master}, {$source})")->fetchAll();
            $byId = array_column($rows, null, 'customer_id');
            $sourceStatus = $byId[$source]['status'] ?? '-';
            $sourceDeleted = ($byId[$source]['deleted_at'] ?? null) !== null;
            $masterStatus = $byId[$master]['status'] ?? '-';
            $opsAfter = $e->count('merge_operations');
            $items = $e->count('merge_items', "source_customer_id = {$source}");
            $result = $body['data']['status'] ?? '-';
            $pass = $status === 200 && $result === 'SUCCESS' && $sourceStatus === 'MERGED' && $sourceDeleted
                && $masterStatus === 'ACTIVE' && $opsAfter === $opsBefore + 1 && $items === 1;
            return [$pass, "HTTP {$status}, data.status {$result}; SOURCE #{$source} {$sourceStatus}, deleted_at " . ($sourceDeleted ? 'co' : 'NULL')
                . "; MASTER #{$master} {$masterStatus}; merge_operations {$opsBefore} -> {$opsAfter}; merge_items cua SOURCE: {$items}"];
        },
    ],
    'TC-04' => [
        'type' => 'Gia tri bien', 'priority' => 'Trung binh', 'api' => false,
        'pre' => 'Khong can CSDL (goi CustomerValidator)',
        'data' => 'validateCreate({full_name:"TC04 Bien Duoi", phone:"09012345"}) - dung 8 chu so',
        'expect' => 'Khong nem ngoai le; phone tra ve "09012345"',
        'run' => function (): array {
            $result = null;
            [$class, $message] = catchException(function () use (&$result): void {
                $result = CustomerValidator::validateCreate(['full_name' => 'TC04 Bien Duoi', 'phone' => '09012345']);
            });
            if ($class !== null) {
                return [false, "Nem {$class}: {$message}"];
            }
            $phone = $result['phone'] ?? '-';
            return [$phone === '09012345', "Khong nem ngoai le; phone \"{$phone}\""];
        },
    ],
    'TC-05' => [
        'type' => 'Gia tri bien', 'priority' => 'Trung binh', 'api' => false,
        'pre' => 'Khong can CSDL (goi CustomerValidator)',
        'data' => 'validateCreate({full_name:"TC05 Bien Duoi", phone:"0901234"}) - 7 chu so',
        'expect' => 'InvalidArgumentException "So dien thoai phai co tu 8 den 15 chu so"',
        'run' => function (): array {
            [$class, $message] = catchException(fn () => CustomerValidator::validateCreate(['full_name' => 'TC05 Bien Duoi', 'phone' => '0901234']));
            $pass = $class === InvalidArgumentException::class && $message === 'So dien thoai phai co tu 8 den 15 chu so';
            return [$pass, $class === null ? 'Khong nem ngoai le' : "{$class} \"{$message}\""];
        },
    ],
    'TC-06' => [
        'type' => 'Du lieu sai', 'priority' => 'Cao', 'api' => false,
        'pre' => 'Khong can CSDL (CustomerValidator + CustomerService voi repository gia)',
        'data' => '{phone:"0901000006"} - thieu full_name',
        'expect' => 'Validator nem InvalidArgumentException "Ho ten khach hang la bat buoc"; service tu choi; repository.create() duoc goi 0 lan',
        'run' => function (): array {
            [$vClass, $vMessage] = catchException(fn () => CustomerValidator::validateCreate(['phone' => '0901000006']));
            $repo = new FakeCustomerRepository();
            [$sClass] = catchException(fn () => (new CustomerService($repo))->createCustomer(['phone' => '0901000006']));
            $pass = $vClass === InvalidArgumentException::class && $vMessage === 'Ho ten khach hang la bat buoc'
                && $sClass === InvalidArgumentException::class && $repo->createCalls === 0;
            return [$pass, 'Validator: ' . ($vClass ?? 'khong nem') . " \"{$vMessage}\"; service: " . ($sClass ?? 'khong nem')
                . "; create() duoc goi {$repo->createCalls} lan"];
        },
    ],
    'TC-07' => [
        'type' => 'Du lieu sai', 'priority' => 'Cao', 'api' => true,
        'pre' => 'Da dang nhap SALES_STAFF; da tao "TC07 Le Van Dau", SDT 0901000007',
        'data' => 'POST /api/customers {full_name:"TC07 Le Van Trung", phone:"0901000007"}',
        'expect' => 'HTTP 409, error BUSINESS_ERROR, message "So dien thoai da ton tai trong he thong"; bang customers KHONG them dong',
        'run' => function (ApiEnv $e): array {
            $e->call($e->sales, 'POST', '/api/customers', ['full_name' => 'TC07 Le Van Dau', 'phone' => '0901000007'], $e->salesToken);
            $before = $e->count('customers');
            [$status, $body] = $e->call($e->sales, 'POST', '/api/customers', ['full_name' => 'TC07 Le Van Trung', 'phone' => '0901000007'], $e->salesToken);
            $after = $e->count('customers');
            $error = $body['error'] ?? '-';
            $message = $body['message'] ?? '-';
            $pass = $status === 409 && $error === 'BUSINESS_ERROR' && $message === 'So dien thoai da ton tai trong he thong' && $after === $before;
            return [$pass, "HTTP {$status}, error {$error}, message \"{$message}\"; customers {$before} -> {$after}"];
        },
    ],
    'TC-08' => [
        'type' => 'Du lieu sai', 'priority' => 'Cao', 'api' => false,
        'pre' => 'Khong can CSDL (MergeService voi PDO gia, moi truy cap CSDL deu bao loi)',
        'data' => 'merge(master=5, source=5, performedBy=1)',
        'expect' => 'DomainException "Khong the gop mot ho so vao chinh no"; khong mo giao dich CSDL',
        'run' => function (): array {
            [$class, $message] = catchException(fn () => (new MergeService(new NoDatabasePDOForCases()))->merge(5, 5, 1));
            $pass = $class === DomainException::class && $message === 'Khong the gop mot ho so vao chinh no';
            return [$pass, $class === null ? 'Khong nem ngoai le' : "{$class} \"{$message}\""];
        },
    ],
    'TC-09' => [
        'type' => 'Chat luong du lieu (QT-02)', 'priority' => 'Cao', 'api' => false,
        'pre' => 'Khong can CSDL (CustomerService voi repository gia)',
        'data' => 'phone: "+84 901 234 567", "84901234567", "0901.234.567", "0901234567"',
        'expect' => 'Ca 4 gia tri luu phone_normalized = "0901234567"',
        'run' => function (): array {
            $actual = [];
            foreach (['+84 901 234 567', '84901234567', '0901.234.567', '0901234567'] as $input) {
                $repo = new FakeCustomerRepository();
                [$class, $message] = catchException(fn () => (new CustomerService($repo))->createCustomer(['full_name' => 'TC09', 'phone' => $input]));
                $actual[$input] = $class === null ? ($repo->rows[1]['phone_normalized'] ?? '-') : "{$class}: {$message}";
            }
            $pass = count(array_unique($actual)) === 1 && reset($actual) === '0901234567';
            $text = [];
            foreach ($actual as $in => $out) {
                $text[] = "\"{$in}\" -> \"{$out}\"";
            }
            return [$pass, implode('; ', $text)];
        },
    ],
    'TC-10' => [
        'type' => 'Bao mat (OWASP A01)', 'priority' => 'Cao', 'api' => true,
        'pre' => 'KHONG dang nhap; CSDL co 3 ho so mau cua seeder',
        'data' => 'GET /api/customers, GET /api/customers/1, GET /api/customers/duplicates',
        'expect' => 'Ca 3 tra HTTP 401, error UNAUTHORIZED; phan hoi khong chua du lieu khach hang (full_name)',
        'run' => function (ApiEnv $e): array {
            $pass = true;
            $text = [];
            foreach (['/api/customers', '/api/customers/1', '/api/customers/duplicates'] as $path) {
                [$status, $body, $raw] = $e->call($e->anonymous, 'GET', $path);
                $leaks = str_contains($raw, 'full_name');
                $pass = $pass && $status === 401 && ($body['error'] ?? '') === 'UNAUTHORIZED' && !$leaks;
                $text[] = "{$path} -> HTTP {$status}" . ($leaks ? ' (CO full_name khach hang)' : '');
            }
            return [$pass, implode('; ', $text)];
        },
    ],
    'TC-11' => [
        'type' => 'Bao mat (OWASP A01)', 'priority' => 'Cao', 'api' => true,
        'pre' => 'Da dang nhap SALES_STAFF (khong phai STORE_MANAGER)',
        'data' => 'GET /api/users',
        'expect' => 'HTTP 403, error FORBIDDEN; khong tra danh sach nhan vien',
        'run' => function (ApiEnv $e): array {
            [$status, $body, $raw] = $e->call($e->sales, 'GET', '/api/users');
            $error = $body['error'] ?? '-';
            $leaks = str_contains($raw, '@smartcrm.local');
            $pass = $status === 403 && $error === 'FORBIDDEN' && !$leaks;
            return [$pass, "HTTP {$status}, error {$error}" . ($leaks ? '; CO lo email nhan vien' : '; khong co du lieu nhan vien')];
        },
    ],
    'TC-12' => [
        'type' => 'Tai lap', 'priority' => 'Cao', 'api' => true,
        'pre' => 'Database tam da chay migration + seeder lan 1',
        'data' => 'Chay lai database/migrate.php va database/seed_bt2.php lan 2',
        'expect' => 'Ca hai ma thoat 0; migration in "SKIP"; seeder khong tao mat khau moi; so dong roles/users/customers/schema_migrations khong doi',
        'run' => function (ApiEnv $e): array {
            $tables = ['roles', 'users', 'customers', 'schema_migrations'];
            $before = array_map(fn ($t) => $e->count($t), array_combine($tables, $tables));
            [$mStatus, $mOut] = $e->runScript('database/migrate.php', $e->target);
            [$sStatus, $sOut] = $e->runScript('database/seed_bt2.php', $e->target);
            $after = array_map(fn ($t) => $e->count($t), array_combine($tables, $tables));
            $skip = str_contains($mOut, 'SKIP');
            $newSecret = str_contains($sOut, 'shown only once');
            $pass = $mStatus === 0 && $sStatus === 0 && $skip && !$newSecret && $before === $after;
            $counts = [];
            foreach ($tables as $t) {
                $counts[] = "{$t} {$before[$t]} -> {$after[$t]}";
            }
            return [$pass, "migrate exit {$mStatus}" . ($skip ? ' (SKIP)' : '') . "; seeder exit {$sStatus}"
                . ($newSecret ? ' (TAO MAT KHAU MOI)' : '') . '; ' . implode(', ', $counts)];
        },
    ],
];

// ---------------------------------------------------------------
// Chay
// ---------------------------------------------------------------

$selected = array_slice($argv, 1);
$unknown = array_diff($selected, array_keys($cases));
if ($unknown) {
    fwrite(STDERR, 'Khong co test case: ' . implode(', ', $unknown) . PHP_EOL);
    exit(1);
}
$selected = $selected ?: array_keys($cases);

$passed = 0;
$failed = 0;
$env = null;
$envError = null;

try {
    foreach ($selected as $id) {
        $case = $cases[$id];
        if ($case['api'] && $env === null && $envError === null) {
            try {
                $env = setUpApiEnv($root);
            } catch (Throwable $ex) {
                // Moi truong API loi: cac test case API FAIL, test Unit van chay tiep.
                $envError = $ex->getMessage();
            }
        }

        try {
            if ($case['api'] && $env === null) {
                [$ok, $actual] = [false, 'Khong dung duoc moi truong kiem thu: ' . $envError];
            } else {
                [$ok, $actual] = $case['api'] ? $case['run']($env) : $case['run']();
            }
        } catch (Throwable $ex) {
            [$ok, $actual] = [false, 'Loi khi chay: ' . get_class($ex) . ': ' . $ex->getMessage()];
        }
        $ok ? $passed++ : $failed++;

        echo "=== {$id} | {$case['type']} | Uu tien: {$case['priority']}" . PHP_EOL;
        echo "Tien dieu kien : {$case['pre']}" . PHP_EOL;
        echo "Du lieu thu    : {$case['data']}" . PHP_EOL;
        echo "Mong doi       : {$case['expect']}" . PHP_EOL;
        echo "Thuc te        : {$actual}" . PHP_EOL;
        echo 'Ket qua        : ' . ($ok ? 'PASS' : 'FAIL') . PHP_EOL . PHP_EOL;
    }
} finally {
    tearDownApiEnv($env);
}

echo "BT3 test cases: {$passed} passed, {$failed} failed, " . ($passed + $failed) . " total" . PHP_EOL;
exit($failed === 0 ? 0 : 1);
