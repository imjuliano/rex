<?php
declare(strict_types=1);

namespace App\User\Service;

use App\Http\Criteria;
use App\Http\PaginatedCollection;
use App\User\Dao\UserDao;
use App\User\Mapper\UserMapper;

final class UserReadService {
    public function __construct(
        private UserDao $dao,
        private UserMapper $mapper,
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

    public function totals(Criteria $criteria): array {
        $t = $this->dao->totals($criteria);
        return [
            'total_users' => (int) ($t['total'] ?? 0),
            'sellers' => (int) ($t['sellers'] ?? 0),
            'admins' => (int) ($t['admins'] ?? 0),
        ];
    }
}
