<?php
declare(strict_types=1);

namespace App\Audit\Exception;

use App\Exception\ErrorCode;
use App\Exception\InfrastructureException;

final class AuditInvalidEncryptedDataException extends InfrastructureException {
    public function __construct() {
        parent::__construct(
            ErrorCode::AUDIT_INVALID_ENCRYPTED_DATA,
            'Invalid audit log encrypted data.'
        );
    }
}
