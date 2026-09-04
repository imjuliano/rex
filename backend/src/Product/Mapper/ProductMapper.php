<?php
declare(strict_types=1);

namespace App\Product\Mapper;

final class ProductMapper {
    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    public function map(array $row): array {
        $active = (bool) $row['active'];
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'sku' => $row['sku'],
            'points_per_unit' => (int) $row['points_per_unit'],
            'status' => $active ? 'active' : 'inactive',
            'active' => $active,
            'created_at' => self::iso($row['created_at']),
        ];
    }

    private static function iso(string $value): string {
        return (new \DateTimeImmutable($value))->format(\DateTimeInterface::ATOM);
    }
}
