<?php
declare(strict_types=1);

namespace App\Tests\Unit\Sale;

use App\Exception\BusinessException;
use App\Sale\ScoringEngine;
use PHPUnit\Framework\TestCase;

final class ScoringEngineTest extends TestCase {
    private ScoringEngine $engine;

    protected function setUp(): void {
        $this->engine = new ScoringEngine();
    }

    public function test_points_for_a_sale(): void {
        $this->assertSame(20, $this->engine->pointsFor(2, 10));
        $this->assertSame(100, $this->engine->pointsFor(4, 25));
        $this->assertSame(0, $this->engine->pointsFor(0, 15));
    }

    public function test_budget_available_is_total_minus_used(): void {
        $this->assertSame(850, $this->engine->budgetAvailable(1000, 150));
        $this->assertSame(0, $this->engine->budgetAvailable(100, 100));
    }

    public function test_sale_fits_budget(): void {
        $this->engine->assertFitsBudget(50, 1, 100, 30);
        $this->expectNotToPerformAssertions();
    }

    public function test_sale_exceeding_budget_is_rejected(): void {
        $this->expectException(BusinessException::class);

        try {
            $this->engine->assertFitsBudget(200, 1, 100, 50);
        } catch (BusinessException $e) {
            $this->assertSame('INSUFFICIENT_BUDGET', $e->errorCode()->value);
            $this->assertSame(1, $e->details()['campaign_id']);
            $this->assertSame(200, $e->details()['requested_points']);
            $this->assertSame(50, $e->details()['available_points']);
            throw $e;
        }
    }
}
