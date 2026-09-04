<?php
declare(strict_types=1);

namespace App\Audit;

enum LogEntity: string {
    case PRODUCT  = 'product';
    case CAMPAIGN = 'campaign';
    case SALE     = 'sale';
    case USER     = 'user';
    case AUTH     = 'auth';

    public function tableName(): string {
        return match ($this) {
            self::PRODUCT  => 'product_audit_log',
            self::CAMPAIGN => 'campaign_audit_log',
            self::SALE     => 'sale_audit_log',
            self::USER     => 'user_audit_log',
            self::AUTH     => 'auth_audit_log',
        };
    }
}
