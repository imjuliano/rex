<?php
declare(strict_types=1);

namespace App\Audit;

final class AuditLogDispatcher {
    public function __construct(private AuditLogWriter $writer) {}

    public function dispatch(AuditEvent $event): void {
        try {
            $this->writer->write($event);
        } catch (\Throwable $e) {
            error_log('Audit dispatch failed: ' . $e->getMessage());
        }
    }
}
