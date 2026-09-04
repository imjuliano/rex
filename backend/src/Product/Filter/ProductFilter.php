<?php
declare(strict_types=1);

namespace App\Product\Filter;

use App\Http\Criteria;
use App\Http\QueryParams;
use App\Product\ProductStatus;

final class ProductFilter {
    /** Public field name => SQL expression. Anything absent is rejected. */
    private const SORTABLE = [
        'id' => 'id',
        'name' => 'name',
        'sku' => 'sku',
        'points_per_unit' => 'points_per_unit',
        'created_at' => 'created_at',
    ];

    public static function from(QueryParams $q): Criteria {
        $criteria = new Criteria(
            self::SORTABLE[$q->sort(self::SORTABLE, 'created_at')],
            $q->order(),
            $q->page(),
            $q->perPage()
        );

        $status = $q->enum('status', ProductStatus::class);
        if ($status !== null) {
            $criteria->add('active = ?', [$status->toFlag()]);
        }

        $search = $q->string('search', 120);
        if ($search !== null) {
            $criteria->add('(name LIKE ? OR sku LIKE ?)', ["%$search%", "%$search%"]);
        }

        if ($q->bool('with_deleted') !== true) {
            $criteria->add('deleted_at IS NULL', []);
        }

        $criteria->addIfNotNull('points_per_unit >= ?', $q->int('min_points', 0));
        $criteria->addIfNotNull('points_per_unit <= ?', $q->int('max_points', 0));

        return $criteria;
    }
}
