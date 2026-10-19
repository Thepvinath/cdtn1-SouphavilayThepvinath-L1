<?php
declare(strict_types=1);

final class CustomerService
{
    public function __construct(
        private CustomerRepository $repository
    ) {
    }

    // FR2: Tra cứu hồ sơ khách hàng
    public function searchCustomers(string $keyword = ''): array
    {
        return $this->repository->findAll(trim($keyword));
    }

    // FR3: Xem chi tiết hồ sơ khách hàng
    public function getCustomer(int $id): ?array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Ma khach hang khong hop le'
            );
        }

        return $this->repository->findById($id);
    }

    // BR2: Chuẩn hóa số điện thoại
    private function normalizePhone(string $phone): string
    {
        $normalized = preg_replace('/[^0-9]/', '', $phone);

        if ($normalized === null || $normalized === '') {
            throw new InvalidArgumentException(
                'So dien thoai khong hop le'
            );
        }

        return $normalized;
    }

    // FR1: Tạo hồ sơ khách hàng
    public function createCustomer(array $input): array
    {
        $fullName = trim((string) ($input['full_name'] ?? ''));
        $phone = trim((string) ($input['phone'] ?? ''));

        if ($fullName === '' || $phone === '') {
            throw new InvalidArgumentException(
                'Ho ten va so dien thoai la bat buoc'
            );
        }

        $normalizedPhone = $this->normalizePhone($phone);

        // BR1: Không tạo hồ sơ với số điện thoại đã tồn tại
        if ($this->repository->findByPhone($normalizedPhone) !== null) {
            throw new DomainException(
                'So dien thoai da ton tai trong he thong'
            );
        }

        $id = $this->repository->create([
            'full_name' => $fullName,
            'phone' => $phone,
            'phone_normalized' => $normalizedPhone,
            'email' => $input['email'] ?? null,
            'address' => $input['address'] ?? null
        ]);

        $customer = $this->repository->findById($id);

        if ($customer === null) {
            throw new RuntimeException(
                'Khong tim thay ho so vua tao'
            );
        }

        return $customer;
    }
    // Cap nhat thong tin khach hang
    public function updateCustomer(int $id, array $data): ?array
    {
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Ma khach hang khong hop le'
            );
        }

        // Kiem tra ho so ton tai va dang hoat dong
        $customer = $this->repository->findById($id);

        if ($customer === null ||
            $customer['status'] !== 'ACTIVE') {
            return null;
        }

        // Goi Repository de cap nhat du lieu
        $this->repository->update($id, $data);

        // Tra ve ho so sau khi cap nhat
        return $this->repository->findById($id);
    }
    // BR3: Soft Delete khach hang
    public function deleteCustomer(int $id): bool
    {
        // Kiem tra ma khach hang
        if ($id <= 0) {
            throw new InvalidArgumentException(
                'Ma khach hang khong hop le'
            );
        }

        // Kiem tra ho so co ton tai khong
        $customer = $this->repository->findById($id);

        if ($customer === null) {
            return false;
        }

        // Chi cho phep xoa ho so ACTIVE
        if ($customer['status'] !== 'ACTIVE') {
            return false;
        }

        // Goi Repository thuc hien Soft Delete
        return $this->repository->softDelete($id);
    }


}
