<?php
declare(strict_types=1);

final class MergeController
{
    public function __construct(
        private MergeService $mergeService
    ) {
    }

    // POST /api/customers/merge
    public function merge(int $performedBy): void
    {
        $input = json_decode(
            file_get_contents('php://input'),
            true
        );

        if (!is_array($input)) {
            throw new InvalidArgumentException(
                'Du lieu JSON khong hop le'
            );
        }

        $masterId = $input['master_customer_id'] ?? null;
        $sourceId = $input['source_customer_id'] ?? null;

        if (
            !is_int($masterId) ||
            !is_int($sourceId) ||
            $masterId <= 0 ||
            $sourceId <= 0
        ) {
            throw new InvalidArgumentException(
                'Ma ho so khach hang khong hop le'
            );
        }

        $result = $this->mergeService->merge(
            $masterId,
            $sourceId,
            $performedBy
        );

        CustomerController::json(200, [
            'message' => 'Gop ho so khach hang thanh cong',
            'data' => $result
        ]);
    }
}
