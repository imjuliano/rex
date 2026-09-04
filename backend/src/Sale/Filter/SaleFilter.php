<?php
declare(strict_types=1);

namespace App\Sale\Filter;

use App\Http\Criteria;
use App\Http\QueryParams;
use App\Sale\SaleStatus;
use App\Validation\Assert;

final class SaleFilter {
    /** Qualified with the alias used by the listing query. */
    private const SORTABLE = [
        'id' => 's.id',
        'external_id' => 's.external_id',
        'quantity' => 's.quantity',
        'unit_value' => 's.unit_value',
        'created_at' => 's.created_at',
    ];

    public static function from(QueryParams $q): Criteria {
        $criteria = new Criteria(
            self::SORTABLE[$q->sort(self::SORTABLE, 'created_at')],
            $q->order(),
            $q->page(),
            $q->perPage()
        );

        $criteria->addIfNotNull('s.status = ?', $q->enum('status', SaleStatus::class)?->value);
        $criteria->addIfNotNull('s.campaign_id = ?', $q->int('campaign_id'));
        $criteria->addIfNotNull('s.seller_id = ?', $q->int('seller_id'));
        $criteria->addIfNotNull('s.product_id = ?', $q->int('product_id'));

        $search = $q->string('search', 255);
        if ($search !== null) {
            $criteria->add('(s.external_id LIKE ? OR p.name LIKE ? OR u.name LIKE ?)', [
                "%$search%",
                "%$search%",
                "%$search%",
            ]);
        }

        $from = $q->string('from', 25);
        if ($from !== null) {
            $criteria->add('s.created_at >= ?', [Assert::dateTime($from, 'from')->format('Y-m-d H:i:s')]);
        }
        $to = $q->string('to', 25);
        if ($to !== null) {
            $criteria->add('s.created_at <= ?', [Assert::dateTime($to, 'to')->format('Y-m-d H:i:s')]);
        }

        return $criteria;
    }
}
