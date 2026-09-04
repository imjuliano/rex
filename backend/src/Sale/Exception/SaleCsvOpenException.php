<?php
declare(strict_types=1);

namespace App\Sale\Exception;

use App\Exception\ErrorCode;
use App\Exception\InfrastructureException;

final class SaleCsvOpenException extends InfrastructureException {
    public function __construct() {
        parent::__construct(
            ErrorCode::CSV_STREAM_OPEN_FAILED,
            'Unable to open temp stream for CSV.'
        );
    }
}
