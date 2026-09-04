<?php
declare(strict_types=1);

namespace App\Tests\Support;

use App\Audit\AuditEvent;
use App\Audit\AuditLogWriter;
use App\Audit\LogAction;

/** Captures dispatched audit events so tests can assert the trail. */
final class RecordingAuditLogWriter implements AuditLogWriter {
    /** @var list<AuditEvent> */
    private array $events = [];

    public function write(AuditEvent $event): void {
        $this->events[] = $event;
    }

    /** @return list<AuditEvent> */
    public function events(): array {
        return $this->events;
    }

    /** @return list<LogAction> */
    public function actions(): array {
        return array_map(fn(AuditEvent $event): LogAction => $event->action, $this->events);
    }

    public function has(LogAction $action): bool {
        return in_array($action, $this->actions(), true);
    }

    public function lastPayload(): array {
        $last = end($this->events);
        return $last === false ? [] : $last->payload;
    }
}
