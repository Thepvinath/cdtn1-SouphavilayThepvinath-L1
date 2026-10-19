<?php
declare(strict_types=1);

// Load các thành phần của hệ thống
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../repositories/CustomerRepository.php';
require_once __DIR__ . '/../services/CustomerService.php';
require_once __DIR__ . '/../validators/CustomerValidator.php';
require_once __DIR__ . '/../controllers/CustomerController.php';

// Đọc HTTP Method và URL
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url(
    $_SERVER['REQUEST_URI'] ?? '/',
    PHP_URL_PATH
) ?: '/';

try {
    // Khởi tạo các lớp theo kiến trúc phân tầng
    $pdo = Database::connect();

    $repository = new CustomerRepository($pdo);
    $service = new CustomerService($repository);
    $controller = new CustomerController($service);

    // GET /api/customers
    // POST /api/customers
    if ($path === '/api/customers' ||
        $path === '/api/customers/') {

        if ($method === 'GET') {
            $controller->index();
            return;
        }

        if ($method === 'POST') {
            $controller->store();
            return;
        }

        header('Allow: GET, POST');

        CustomerController::json(405, [
            'error' => 'METHOD_NOT_ALLOWED',
            'message' => 'Phuong thuc khong duoc ho tro'
        ]);
        return;
    }

    
    // GET /api/customers/{id}
    // PUT /api/customers/{id}
    if (preg_match(
        '#^/api/customers/([1-9][0-9]*)/?$#',
        $path,
        $matches
    )) {
        $id = (int) $matches[1];

        if ($method === 'GET') {
            $controller->show($id);
            return;
        }

        if ($method === 'PUT') {
            $controller->update($id);
            return;
        }
        // DELETE /api/customers/{id}
        if ($method === 'DELETE') {
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


    // Endpoint không tồn tại
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
    // Không trả chi tiết lỗi nội bộ cho người dùng
    error_log($e->getMessage());

    CustomerController::json(500, [
        'error' => 'INTERNAL_SERVER_ERROR',
        'message' => 'He thong gap loi'
    ]);
}
