<?php
declare(strict_types=1);

namespace App\Audit\Filter;

use App\Http\Criteria;
use App\Http\QueryParams;

final class AuditLogFilter {
    private const SORTABLE = [
        'id' => 'id',
        'occurred_at' => 'occurred_at',
        'action' => 'action',
    ];

    public static function from(QueryParams $q): Criteria {
        $criteria = new Criteria(
            self::SORTABLE[$q->sort(self::SORTABLE, 'occurred_at')],
            $q->order('desc'),
            $q->page(),
            $q->perPage()
        );

        $action = $q->int('action', 0);
        if ($action > 0) {
            $criteria->add('action = ?', [$action]);
        }

        $actorId = $q->int('actor_id', 0);
        if ($actorId > 0) {
            $criteria->add('actor_id = ?', [$actorId]);
        }

        $entityId = $q->string('entity_id', 120);
        if ($entityId !== null) {
            $criteria->add('entity_id = ?', [$entityId]);
        }

        $search = $q->string('search', 120);
        if ($search !== null) {
            $criteria->add('entity_id LIKE ?', ["%$search%"]);
        }

        $from = $q->string('from', 25);
        if ($from !== null) {
            $criteria->add('occurred_at >= ?', [$from]);
        }

        $to = $q->string('to', 25);
        if ($to !== null) {
            $criteria->add('occurred_at <= ?', [$to]);
        }

        return $criteria;
    }
}
