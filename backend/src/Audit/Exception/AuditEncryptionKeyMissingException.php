<?php
declare(strict_types=1);

namespace App\Audit\Exception;

use App\Exception\ErrorCode;
use App\Exception\InfrastructureException;

final class AuditEncryptionKeyMissingException extends InfrastructureException {
    public function __construct() {
        parent::__construct(
            ErrorCode::AUDIT_ENCRYPTION_KEY_MISSING,
            'AUDIT_LOG_ENCRYPTION_KEY must be set.'
        );
    }
}
