<?php
declare(strict_types=1);

final class UserController
{
    public function __construct(private UserService $service) {}

    public function handle(string $method, ?int $id, int $actorId): void
    {
        if ($method === 'GET') {
            $data = $id === null ? $this->service->all() : $this->service->find($id);
        } elseif ($method === 'DELETE') {
            $data = $this->service->disable($id, $actorId);
        } else {
            $input = json_decode(file_get_contents('php://input') ?: '');
            if (!$input instanceof stdClass) throw new InvalidArgumentException('Expected a JSON object');
            $data = $this->service->save((array) $input, $actorId, $id);
        }
        if ($data === null) {
            CustomerController::json(404, ['error' => 'USER_NOT_FOUND', 'message' => 'User not found']);
            return;
        }
        CustomerController::json($method === 'POST' ? 201 : 200, ['data' => $data]);
    }
}
