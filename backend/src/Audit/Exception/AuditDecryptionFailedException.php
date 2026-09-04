<?php
declare(strict_types=1);

namespace App\Audit\Exception;

use App\Exception\ErrorCode;
use App\Exception\InfrastructureException;

final class AuditDecryptionFailedException extends InfrastructureException {
    public function __construct() {
        parent::__construct(
            ErrorCode::AUDIT_DECRYPTION_FAILED,
            'Failed to decrypt audit log data.'
        );
    }
}
