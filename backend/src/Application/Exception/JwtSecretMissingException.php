<?php
declare(strict_types=1);

namespace App\Application\Exception;

use App\Exception\ErrorCode;
use App\Exception\InfrastructureException;

final class JwtSecretMissingException extends InfrastructureException {
    public function __construct() {
        parent::__construct(
            ErrorCode::CONFIGURATION_MISSING,
            'JWT_SECRET environment variable is required and cannot be empty.'
        );
    }
}
