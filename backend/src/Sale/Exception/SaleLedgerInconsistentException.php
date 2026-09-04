<?php
declare(strict_types=1);

namespace App\Sale\Exception;

use App\Exception\ErrorCode;
use App\Exception\InfrastructureException;

final class SaleLedgerInconsistentException extends InfrastructureException {
    public function __construct(int $saleId) {
        parent::__construct(
            ErrorCode::LEDGER_INCONSISTENT,
            'The sale ledger is inconsistent.',
            ['sale_id' => $saleId]
        );
    }
}
