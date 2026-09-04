<?php
declare(strict_types=1);

namespace App\Audit;

interface AuditLogWriter {
    public function write(AuditEvent $event): void;
}
