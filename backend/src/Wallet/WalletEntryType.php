<?php
declare(strict_types=1);

namespace App\Wallet;

enum WalletEntryType: string {
    case CREDIT = 'credit';
    case DEBIT = 'debit';

    /** Ledger sign applied when summing the balance. */
    public function sign(): int {
        return $this === self::CREDIT ? 1 : -1;
    }
}
