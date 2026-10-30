<?php
declare(strict_types=1);

final class UserService
{
    public function __construct(private UserRepository $repository) {}

    public function all(): array { return $this->repository->all(); }
    public function find(int $id): ?array { return $this->repository->find($id); }

    public static function guardManagerRemoval(array $target, int $managerCount): void
    {
        if ((bool) $target['is_active'] && $target['role_name'] === 'STORE_MANAGER' && $managerCount <= 1) {
            throw new DomainException('Cannot remove the last active manager');
        }
    }

    public function save(array $input, int $actorId, ?int $id = null): ?array
    {
        $data = UserValidator::validate($input, $id === null);
        return $this->repository->transaction(function () use ($data, $actorId, $id): ?array {
            $this->authorize($actorId);
            if ($id !== null) {
                $target = $this->repository->find($id);
                if (!$target) return null;
                if ($data['role_name'] !== 'STORE_MANAGER') {
                    self::guardManagerRemoval($target, $this->repository->activeManagers());
                }
            }
            if ($this->repository->emailExists($data['email'], $id ?? 0)) {
                throw new DomainException('Email already exists');
            }
            return $this->repository->find($this->repository->save($data, $id));
        });
    }

    private function authorize(int $actorId): void
    {
        $actor = $this->repository->find($actorId);
        if (!$actor || !(bool) $actor['is_active'] || $actor['role_name'] !== 'STORE_MANAGER') {
            throw new DomainException('Active manager account required');
        }
    }

    public function disable(int $id, int $actorId): ?array
    {
        return $this->repository->transaction(function () use ($id, $actorId): ?array {
            $this->authorize($actorId);
            $target = $this->repository->find($id);
            if (!$target) return null;
            if ($id === $actorId) throw new DomainException('Cannot disable your own account');
            self::guardManagerRemoval($target, $this->repository->activeManagers());
            $this->repository->disable($id);
            return $this->repository->find($id);
        });
    }
}
