<?php
declare(strict_types=1);

namespace App\Campaign\Filter;

use App\Campaign\CampaignStatus;
use App\Http\Criteria;
use App\Http\QueryParams;

final class CampaignFilter {
    private const SORTABLE = [
        'id' => 'id',
        'name' => 'name',
        'budget_total' => 'budget_total',
        'budget_used' => 'budget_used',
        'starts_at' => 'starts_at',
        'ends_at' => 'ends_at',
        'created_at' => 'created_at',
    ];

    public static function from(QueryParams $q): Criteria {
        $criteria = new Criteria(
            self::SORTABLE[$q->sort(self::SORTABLE, 'created_at')],
            $q->order(),
            $q->page(),
            $q->perPage()
        );

        $status = $q->enum('status', CampaignStatus::class);
        $criteria->addIfNotNull('status = ?', $status?->value);

        $search = $q->string('search', 120);
        if ($search !== null) {
            $criteria->add('name LIKE ?', ["%$search%"]);
        }

        // Campaigns that are open right now: active and inside the period.
        if ($q->bool('running') === true) {
            $criteria->add('status = ? AND NOW() BETWEEN starts_at AND ends_at', [CampaignStatus::ACTIVE->value]);
        }

        // Campaigns whose budget is fully consumed.
        $exhausted = $q->bool('exhausted');
        if ($exhausted === true) {
            $criteria->add('budget_used >= budget_total');
        } elseif ($exhausted === false) {
            $criteria->add('budget_used < budget_total');
        }

        return $criteria;
    }
}
