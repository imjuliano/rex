<?php
declare(strict_types=1);

namespace App\Wallet\Filter;

use App\Http\Criteria;
use App\Http\QueryParams;
use App\Validation\Assert;
use App\Wallet\WalletEntryType;

final class WalletFilter {
    private const SORTABLE = [
        'id' => 'id',
        'points' => 'points',
        'created_at' => 'created_at',
    ];

    /**
     * Ownership is not a filter the client can influence: the seller id is
     * always injected from the token by the caller.
     */
    public static function from(QueryParams $q, int $sellerId): Criteria {
        $criteria = new Criteria(
            self::SORTABLE[$q->sort(self::SORTABLE, 'created_at')],
            $q->order(),
            $q->page(),
            $q->perPage()
        );

        $criteria->add('seller_id = ?', [$sellerId]);
        $criteria->addIfNotNull('type = ?', $q->enum('type', WalletEntryType::class)?->value);
        $criteria->addIfNotNull('campaign_id = ?', $q->int('campaign_id'));
        $criteria->addIfNotNull('sale_id = ?', $q->int('sale_id'));

        $search = $q->string('search', 255);
        if ($search !== null) {
            $criteria->add('description LIKE ?', ["%$search%"]);
        }

        $from = $q->string('from', 25);
        if ($from !== null) {
            $criteria->add('created_at >= ?', [Assert::dateTime($from, 'from')->format('Y-m-d H:i:s')]);
        }
        $to = $q->string('to', 25);
        if ($to !== null) {
            $criteria->add('created_at <= ?', [Assert::dateTime($to, 'to')->format('Y-m-d H:i:s')]);
        }

        return $criteria;
    }
}
