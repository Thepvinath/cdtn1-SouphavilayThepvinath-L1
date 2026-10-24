<?php
declare(strict_types=1);

final class MergeService
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    // FR5 - Merge duplicate customer profiles
    public function merge(
        int $masterId,
        int $sourceId,
        int $performedBy
    ): array {
        if (
            $masterId <= 0 ||
            $sourceId <= 0 ||
            $performedBy <= 0
        ) {
            throw new InvalidArgumentException(
                'ID khong hop le'
            );
        }

        if ($masterId === $sourceId) {
            throw new DomainException(
                'Khong the gop mot ho so vao chinh no'
            );
        }

        $this->pdo->beginTransaction();

        try {
            // Lock both customers to prevent concurrent merges
            $stmt = $this->pdo->prepare("
                SELECT
                    customer_id,
                    phone_normalized,
                    status,
                    deleted_at
                FROM customers
                WHERE customer_id IN (?, ?)
                ORDER BY customer_id
                FOR UPDATE
            ");

            $stmt->execute([$masterId, $sourceId]);

            $records = [];

            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $records[(int) $row['customer_id']] = $row;
            }

            $master = $records[$masterId] ?? null;
            $source = $records[$sourceId] ?? null;

            if ($master === null || $source === null) {
                throw new DomainException(
                    'Ho so khach hang khong ton tai'
                );
            }

            foreach ([$master, $source] as $customer) {
                if (
                    $customer['status'] !== 'ACTIVE' ||
                    $customer['deleted_at'] !== null
                ) {
                    throw new DomainException(
                        'Chi duoc gop ho so ACTIVE'
                    );
                }
            }

            // Current duplicate-detection rule:
            // normalized phone numbers must match
            if (
                $master['phone_normalized'] !==
                $source['phone_normalized']
            ) {
                throw new DomainException(
                    'Hai ho so khong trung so dien thoai'
                );
            }

            // 1. Save merge operation
            $stmt = $this->pdo->prepare("
                INSERT INTO merge_operations (
                    master_customer_id,
                    performed_by
                )
                VALUES (?, ?)
            ");

            $stmt->execute([
                $masterId,
                $performedBy
            ]);

            $mergeId = (int) $this->pdo->lastInsertId();

            // 2. Save source profile in merge history
            $stmt = $this->pdo->prepare("
                INSERT INTO merge_items (
                    merge_id,
                    source_customer_id
                )
                VALUES (?, ?)
            ");

            $stmt->execute([
                $mergeId,
                $sourceId
            ]);

            // 3. Soft-delete the duplicate profile
            $stmt = $this->pdo->prepare("
                UPDATE customers
                SET
                    status = 'MERGED',
                    deleted_at = NOW()
                WHERE customer_id = ?
                  AND status = 'ACTIVE'
                  AND deleted_at IS NULL
            ");

            $stmt->execute([$sourceId]);

            if ($stmt->rowCount() !== 1) {
                throw new RuntimeException(
                    'Khong the cap nhat ho so trung'
                );
            }

            // 4. Commit only when every step succeeds
            $this->pdo->commit();

            return [
                'merge_id' => $mergeId,
                'master_customer_id' => $masterId,
                'source_customer_id' => $sourceId,
                'status' => 'SUCCESS'
            ];

        } catch (Throwable $e) {
            // NFR4 - Rollback all changes on failure
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $e;
        }
    }
}
