<?php
declare(strict_types=1);

class CustomerRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    // Lấy danh sách và tra cứu khách hàng
    public function findAll(string $keyword = ''): array
    {
        $sql = "
            SELECT *
            FROM customers
            WHERE deleted_at IS NULL
              AND status = 'ACTIVE'
              AND (
                  full_name LIKE :name
                  OR phone_normalized LIKE :phone
              )
            ORDER BY customer_id DESC
            LIMIT 100
        ";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            'name' => '%' . $keyword . '%',
            'phone' => '%' . $keyword . '%'
        ]);

        return $stmt->fetchAll();
    }

    // Tìm khách hàng theo ID
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM customers
            WHERE customer_id = :id
              AND deleted_at IS NULL
        ");

        $stmt->execute(['id' => $id]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    // Kiểm tra số điện thoại đã tồn tại
    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT *
            FROM customers
            WHERE phone_normalized = :phone
              AND status = 'ACTIVE'
              AND deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->execute(['phone' => $phone]);

        $result = $stmt->fetch();

        return $result ?: null;
    }

    // Thêm hồ sơ khách hàng mới
    public function create(array $data): int
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO customers
                (full_name, phone, phone_normalized,
                 email, address)
            VALUES
                (:full_name, :phone, :phone_normalized,
                 :email, :address)
        ");

        $stmt->execute([
            'full_name' => $data['full_name'],
            'phone' => $data['phone'],
            'phone_normalized' => $data['phone_normalized'],
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null
        ]);

        return (int) $this->pdo->lastInsertId();
    }
public function update(int $id, array $data): bool
{
    $stmt = $this->pdo->prepare("
        UPDATE customers
        SET full_name = :full_name,
            email = :email,
            address = :address
        WHERE customer_id = :id
          AND status = 'ACTIVE'
          AND deleted_at IS NULL
    ");

    $stmt->execute([
        'id' => $id,
        'full_name' => $data['full_name'],
        'email' => $data['email'] ?? null,
        'address' => $data['address'] ?? null
    ]);

    return $stmt->rowCount() > 0;
}
    // Soft Delete - BR3
    public function softDelete(int $id): bool
    {
        $stmt = $this->pdo->prepare("
            UPDATE customers
            SET deleted_at = CURRENT_TIMESTAMP
            WHERE customer_id = :id
              AND status = 'ACTIVE'
              AND deleted_at IS NULL
        ");

        $stmt->execute([
            'id' => $id
        ]);

        return $stmt->rowCount() > 0;
    }


}
