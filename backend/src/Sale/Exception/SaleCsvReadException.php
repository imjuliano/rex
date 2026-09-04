<?php
declare(strict_types=1);

namespace App\Sale\Exception;

use App\Exception\ErrorCode;
use App\Exception\InfrastructureException;

final class SaleCsvReadException extends InfrastructureException {
    public function __construct() {
        parent::__construct(
            ErrorCode::CSV_STREAM_READ_FAILED,
            'Unable to read CSV stream.'
        );
    }
}
