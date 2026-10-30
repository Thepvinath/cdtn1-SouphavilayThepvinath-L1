<?php
declare(strict_types=1);

final class DuplicateDetectionService
{
    public function __construct(
        private CustomerRepository $repository
    ) {
    }

    // FR4: Phat hien ho so khach hang nghi trung
    public function getPotentialDuplicates(): array
    {
        return $this->repository->findPotentialDuplicates();
    }
}
