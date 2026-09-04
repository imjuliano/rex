<?php
declare(strict_types=1);

namespace App\Campaign\Service;

use App\Campaign\Dao\CampaignDao;
use App\Campaign\Mapper\CampaignMapper;
use App\Http\Criteria;
use App\Http\PaginatedCollection;

final class CampaignReadService {
    public function __construct(
        private CampaignDao $dao,
        private CampaignMapper $mapper,
    ) {}

    public function list(Criteria $criteria): PaginatedCollection {
        $page = $this->dao->list($criteria);
        return new PaginatedCollection(
            array_map([$this->mapper, 'map'], $page->items),
            $page->total,
            $page->page,
            $page->perPage
        );
    }

    public function totals(): array {
        $t = $this->dao->totals();
        $budgetTotal = (int) $t['budget_total_sum'];
        $budgetUsed = (int) $t['budget_used_sum'];
        $remaining = $budgetTotal - $budgetUsed;
        $pct = $budgetTotal > 0 ? (int) round($budgetUsed / $budgetTotal * 100) : 0;
        return [
            'total_campaigns' => (int) $t['total'],
            'active_campaigns' => (int) $t['active_running'],
            'exhausted' => (int) $t['exhausted'],
            'budget_total' => $budgetTotal,
            'budget_used' => $budgetUsed,
            'budget_remaining' => $remaining,
            'budget_usage_pct' => $pct,
        ];
    }
}
