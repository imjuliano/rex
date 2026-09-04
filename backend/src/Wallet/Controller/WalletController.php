<?php
declare(strict_types=1);

namespace App\Wallet\Controller;

use App\Http\ApiResponse;
use App\Http\QueryParams;
use App\Wallet\Filter\WalletFilter;
use App\Wallet\Service\WalletReadService;

final class WalletController {
    public function __construct(private WalletReadService $reads) {}

    public function list(QueryParams $query, int $sellerId): void {
        $criteria = WalletFilter::from($query, $sellerId);
        $page = $this->reads->list($criteria);
        $summary = $this->reads->summary($sellerId);
        ApiResponse::collection($page, '/me/wallet', $query->all(), $summary);
    }
}
