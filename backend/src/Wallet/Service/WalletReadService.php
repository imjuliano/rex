<?php
declare(strict_types=1);

namespace App\Wallet\Service;

use App\Http\Criteria;
use App\Http\PaginatedCollection;
use App\Wallet\Dao\WalletDao;
use App\Wallet\Mapper\WalletMapper;

final class WalletReadService {
    public function __construct(
        private WalletDao $dao,
        private WalletMapper $mapper,
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

    public function summary(int $sellerId): array {
        $s = $this->dao->summary($sellerId);
        return [
            'balance' => (int) $s['balance'],
            'credited' => (int) $s['credited'],
            'debited' => (int) $s['debited'],
            'credit_entries' => (int) $s['credit_entries'],
            'debit_entries' => (int) $s['debit_entries'],
            'total_entries' => (int) $s['total_entries'],
            'avg_points_per_credit' => round((float) $s['avg_points_per_credit'], 2),
        ];
    }
}
