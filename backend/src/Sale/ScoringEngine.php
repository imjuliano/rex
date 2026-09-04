<?php
declare(strict_types=1);

namespace App\Sale;

use App\Sale\Exception\SaleInsufficientBudgetException;

/**
 * Pure business rules for the scoring engine. No I/O, no database.
 */
final class ScoringEngine {
    public function pointsFor(int $quantity, int $pointsPerUnit): int {
        return $quantity * $pointsPerUnit;
    }

    public function budgetAvailable(int $budgetTotal, int $budgetUsed): int {
        return $budgetTotal - $budgetUsed;
    }

    public function assertFitsBudget(
        int $points,
        int $campaignId,
        int $budgetTotal,
        int $budgetUsed
    ): void {
        $available = $this->budgetAvailable($budgetTotal, $budgetUsed);
        if ($points > $available) {
            throw new SaleInsufficientBudgetException($campaignId, $points, $available);
        }
    }
}
