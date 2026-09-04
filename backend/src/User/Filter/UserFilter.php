<?php
declare(strict_types=1);

namespace App\User\Filter;

use App\Http\Criteria;
use App\Http\QueryParams;

final class UserFilter {
    private const SORTABLE = [
        'id' => 'id',
        'name' => 'name',
        'email' => 'email',
        'created_at' => 'created_at',
    ];

    public static function from(QueryParams $q): Criteria {
        $criteria = new Criteria(
            self::SORTABLE[$q->sort(self::SORTABLE, 'name')],
            $q->order('asc'),
            $q->page(),
            $q->perPage()
        );

        $search = $q->string('search', 120);
        if ($search !== null) {
            $criteria->add('(name LIKE ? OR email LIKE ?)', ["%$search%", "%$search%"]);
        }

        $role = $q->string('role', 20);
        if ($role !== null) {
            $criteria->add('role = ?', [$role]);
        }

        if ($q->bool('with_deleted') !== true) {
            $criteria->add('deleted_at IS NULL', []);
        }

        return $criteria;
    }
}
