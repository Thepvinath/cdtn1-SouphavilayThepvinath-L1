<?php
declare(strict_types=1);

// ============================================
// 1. PHP SESSION CONFIGURATION
// ============================================

$localAppData = getenv('LOCALAPPDATA');

if (!$localAppData) {
    throw new RuntimeException(
        'LOCALAPPDATA environment variable is missing'
    );
}

$sessionDir = $localAppData . '\\SmartCRM\\sessions';

if (!is_dir($sessionDir) || !is_writable($sessionDir)) {
    throw new RuntimeException(
        'Session directory does not exist or is not writable'
    );
}

ini_set('session.save_path', $sessionDir);
ini_set('session.use_strict_mode', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS'])
        && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();

// ============================================
// 2. LOAD SYSTEM COMPONENTS
// ============================================

// Database
require_once __DIR__ . '/../config/Database.php';

// Repositories
require_once __DIR__ . '/../repositories/CustomerRepository.php';
require_once __DIR__ . '/../repositories/AuthRepository.php';

// Validators
require_once __DIR__ . '/../validators/CustomerValidator.php';

// Services
require_once __DIR__ . '/../services/CustomerService.php';
require_once __DIR__ . '/../services/DuplicateDetectionService.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/MergeService.php';

// Controllers
require_once __DIR__ . '/../controllers/CustomerController.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/MergeController.php';

// Security
require_once __DIR__ . '/../security/RequestGuard.php';

// ============================================
// 3. HTTP REQUEST INFORMATION
// ============================================

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

$path = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
) ?: '/';

try {

    // ========================================
    // 4. DEPENDENCY INITIALIZATION
    // ========================================

    $pdo = Database::connect();

    // Customer components
    $repository = new CustomerRepository($pdo);

    $service = new CustomerService($repository);

    $duplicateService = new DuplicateDetectionService(
        $repository
    );

    $controller = new CustomerController(
        $service,
        $duplicateService
    );

    // Authentication components
    $authRepository = new AuthRepository($pdo);

    $authService = new AuthService($authRepository);

    $authController = new AuthController($authService);

    // Merge components - FR5
    $mergeService = new MergeService($pdo);

    $mergeController = new MergeController(
        $mergeService
    );

    // ========================================
    // 5. AUTHENTICATION ROUTES
    // ========================================

    // GET /api/auth/csrf
    if ($path === '/api/auth/csrf') {

        if ($method === 'GET') {
            CustomerController::json(200, [
                'data' => [
                    'csrf_token' => RequestGuard::csrfToken()
                ]
            ]);
            return;
        }

        header('Allow: GET');

        CustomerController::json(405, [
            'error' => 'METHOD_NOT_ALLOWED',
            'message' => 'Chi ho tro GET'
        ]);
        return;
    }

    // POST /api/auth/login
    if ($path === '/api/auth/login') {

        if ($method === 'POST') {

            RequestGuard::verifyCsrf();

            $authController->login();
            return;
        }

        header('Allow: POST');

        CustomerController::json(405, [
            'error' => 'METHOD_NOT_ALLOWED',
            'message' => 'Chi ho tro POST'
        ]);
        return;
    }

    // GET /api/auth/me
    if ($path === '/api/auth/me') {

        if ($method === 'GET') {
            $authController->me();
            return;
        }

        header('Allow: GET');

        CustomerController::json(405, [
            'error' => 'METHOD_NOT_ALLOWED',
            'message' => 'Chi ho tro GET'
        ]);
        return;
    }

    // POST /api/auth/logout
    if ($path === '/api/auth/logout') {

        if ($method === 'POST') {

            RequestGuard::requireLogin();
            RequestGuard::verifyCsrf();

            $authController->logout();
            return;
        }

        header('Allow: POST');

        CustomerController::json(405, [
            'error' => 'METHOD_NOT_ALLOWED',
            'message' => 'Chi ho tro POST'
        ]);
        return;
    }

    // ========================================
    // 6. CUSTOMER COLLECTION ROUTES
    // ========================================

    // GET /api/customers
    // POST /api/customers

    if (
        $path === '/api/customers' ||
        $path === '/api/customers/'
    ) {

        // GET - List or search customers
        if ($method === 'GET') {
            $controller->index();
            return;
        }

        // POST - Create customer
        if ($method === 'POST') {

            RequestGuard::requireRole([
                'SALES_STAFF',
                'STORE_MANAGER'
            ]);

            RequestGuard::verifyCsrf();

            $controller->store();
            return;
        }

        header('Allow: GET, POST');

        CustomerController::json(405, [
            'error' => 'METHOD_NOT_ALLOWED',
            'message' => 'Chi ho tro GET va POST'
        ]);
        return;
    }

    // ========================================
    // 7. DUPLICATE DETECTION (FR4)
    // ========================================

    // GET /api/customers/duplicates
    if (
        $path === '/api/customers/duplicates' ||
        $path === '/api/customers/duplicates/'
    ) {

        if ($method === 'GET') {
            $controller->duplicates();
            return;
        }

        header('Allow: GET');

        CustomerController::json(405, [
            'error' => 'METHOD_NOT_ALLOWED',
            'message' => 'Chi ho tro GET'
        ]);
        return;
    }

    // ========================================
    // 8. MERGE DUPLICATE CUSTOMERS (FR5)
    // ========================================

    // POST /api/customers/merge
    if (
        $path === '/api/customers/merge' ||
        $path === '/api/customers/merge/'
    ) {

        if ($method === 'POST') {

            // Check login and role
            $user = RequestGuard::requireRole([
                'SALES_STAFF',
                'STORE_MANAGER'
            ]);

            // Check CSRF token
            RequestGuard::verifyCsrf();

            // Get user ID from authenticated session
            $performedBy = (int) $user['user_id'];

            // Execute customer merge
            $mergeController->merge($performedBy);

            return;
        }

        header('Allow: POST');

        CustomerController::json(405, [
            'error' => 'METHOD_NOT_ALLOWED',
            'message' => 'Chi ho tro POST'
        ]);
        return;
    }

    // ========================================
    // 9. CUSTOMER DETAIL ROUTES
    // ========================================

    // GET /api/customers/{id}
    // PUT /api/customers/{id}
    // DELETE /api/customers/{id}

    if (preg_match(
        '#^/api/customers/([1-9][0-9]*)/?$#',
        $path,
        $matches
    )) {

        $id = (int) $matches[1];

        // GET - Customer detail
        if ($method === 'GET') {
            $controller->show($id);
            return;
        }

        // PUT - Update customer
        if ($method === 'PUT') {

            RequestGuard::requireRole([
                'SALES_STAFF',
                'STORE_MANAGER'
            ]);

            RequestGuard::verifyCsrf();

            $controller->update($id);
            return;
        }

        // DELETE - Soft delete customer
        if ($method === 'DELETE') {

            RequestGuard::requireRole([
                'SALES_STAFF',
                'STORE_MANAGER'
            ]);

            RequestGuard::verifyCsrf();

            $controller->destroy($id);
            return;
        }

        header('Allow: GET, PUT, DELETE');

        CustomerController::json(405, [
            'error' => 'METHOD_NOT_ALLOWED',
            'message' => 'Chi ho tro GET, PUT va DELETE'
        ]);
        return;
    }

    // ========================================
    // 10. UNKNOWN ENDPOINT
    // ========================================

    CustomerController::json(404, [
        'error' => 'NOT_FOUND',
        'message' => 'API khong ton tai'
    ]);

} catch (InvalidArgumentException $e) {

    CustomerController::json(400, [
        'error' => 'VALIDATION_ERROR',
        'message' => $e->getMessage()
    ]);

} catch (DomainException $e) {

    CustomerController::json(409, [
        'error' => 'BUSINESS_ERROR',
        'message' => $e->getMessage()
    ]);

} catch (Throwable $e) {

    // Do not expose internal errors
    error_log($e->getMessage());

    CustomerController::json(500, [
        'error' => 'INTERNAL_SERVER_ERROR',
        'message' => 'He thong gap loi'
    ]);
}
