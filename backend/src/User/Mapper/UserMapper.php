<?php
declare(strict_types=1);

namespace App\User\Mapper;

final class UserMapper {
    public function map(array $r): array {
        return [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'email' => $r['email'],
            'role' => $r['role'],
            'created_at' => (new \DateTimeImmutable($r['created_at']))->format(\DateTimeInterface::ATOM),
        ];
    }
}
