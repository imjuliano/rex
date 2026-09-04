<?php
declare(strict_types=1);

namespace App\Validation;

/**
 * Single source of truth for input bounds.
 *
 * Every value here mirrors a column definition in db/init.sql. Keeping them
 * in one place is what stops a 300-character name from reaching MySQL and
 * turning a user typo into a 500, and it gives the frontend a list it can
 * copy verbatim into maxLength attributes.
 */
final class Limits {
    private function __construct() {}

    /** Largest value a signed MySQL INT column can hold. */
    public const INT_MAX = 2147483647;

    // String lengths — VARCHAR(n) in the schema.
    public const USER_NAME = 255;
    public const USER_EMAIL = 255;
    public const PASSWORD = 1024;
    public const PRODUCT_NAME = 100;
    public const PRODUCT_SKU = 100;
    public const CAMPAIGN_NAME = 255;
    public const SALE_EXTERNAL_ID = 255;

    // Numeric ranges.
    public const POINTS_PER_UNIT_MAX = self::INT_MAX;
    public const BUDGET_TOTAL_MAX = self::INT_MAX;
    public const QUANTITY_MAX = 1000000;

    /**
     * unit_value is DECIMAL(10,2): ten digits total, two of them decimal,
     * so the largest representable value is 99999999.99.
     */
    public const UNIT_VALUE_MAX = 99999999.99;
}
