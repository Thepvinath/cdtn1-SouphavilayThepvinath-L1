<?php
declare(strict_types=1);

final class CustomerController
{
    public function __construct(
        private CustomerService $service
    ) {
    }

    // GET /api/customers
    public function index(): void
    {
        $keyword = (string) ($_GET['search'] ?? '');

        $customers = $this->service->searchCustomers($keyword);

        $results = array_map(
            [self::class, 'maskCustomer'],
            $customers
        );

        self::json(200, ['data' => $results]);
    }

    // GET /api/customers/{id}
    public function show(int $id): void
    {
        $customer = $this->service->getCustomer($id);

        if ($customer === null) {
            self::json(404, [
                'error' => 'CUSTOMER_NOT_FOUND',
                'message' => 'Khong tim thay khach hang'
            ]);
            return;
        }

        self::json(200, [
            'data' => self::maskCustomer($customer)
        ]);
    }

    // POST /api/customers
    public function store(): void
    {
        $body = file_get_contents('php://input');

$input = json_decode($body ?: '');

if (!($input instanceof stdClass)) {
    throw new InvalidArgumentException(
        'Du lieu JSON khong hop le'
    );
}

$validated = CustomerValidator::validateCreate((array) $input);


        $customer = $this->service->createCustomer($validated);

        self::json(201, [
            'message' => 'Tao ho so khach hang thanh cong',
            'data' => self::maskCustomer($customer)
        ]);
    }

    // Che số điện thoại theo nguyên tắc NFR3
    // Tạm thời che cho mọi vai trò vì chưa có RBAC
    private static function maskCustomer(array $customer): array
    {
        $phone = (string) ($customer['phone'] ?? '');
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (strlen($digits) > 4) {
            $customer['phone'] =
                substr($digits, 0, 2)
                . str_repeat('*', strlen($digits) - 4)
                . substr($digits, -2);
        } else {
            $customer['phone'] = '****';
        }

        unset($customer['phone_normalized']);

        return $customer;
    }

    public static function json(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
    // PUT /api/customers/{id}
    public function update(int $id): void
    {
        $body = file_get_contents('php://input');
        $input = json_decode($body ?: '');

        // PHP 8.0 compatible
        if (!($input instanceof stdClass)) {
            throw new InvalidArgumentException(
                'Du lieu JSON khong hop le'
            );
        }

        $data = (array) $input;

        // Khong cho thay doi so dien thoai trong thao tac nay
        if (array_key_exists('phone', $data) ||
            array_key_exists('phone_normalized', $data)) {
            throw new InvalidArgumentException(
                'Khong duoc thay doi so dien thoai'
            );
        }

        // Kiem tra du lieu dau vao
        $validated = CustomerValidator::validateUpdate($data);

        // Goi Business Service
        $customer = $this->service->updateCustomer($id, $validated);

        if ($customer === null) {
            self::json(404, [
                'error' => 'CUSTOMER_NOT_FOUND',
                'message' => 'Khong tim thay ho so dang hoat dong'
            ]);
            return;
        }

        self::json(200, [
            'message' => 'Cap nhat khach hang thanh cong',
            'data' => self::maskCustomer($customer)
        ]);
    }
    // DELETE /api/customers/{id}
    // BR3: Soft Delete
    public function destroy(int $id): void
    {
        $deleted = $this->service->deleteCustomer($id);

        if (!$deleted) {
            self::json(404, [
                'error' => 'CUSTOMER_NOT_FOUND',
                'message' => 'Khong tim thay ho so dang hoat dong'
            ]);
            return;
        }

        self::json(200, [
            'message' => 'Xoa mem ho so khach hang thanh cong',
            'customer_id' => $id
        ]);
    }


}
