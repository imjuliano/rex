<?php
declare(strict_types=1);

namespace App\Sale\Service;

use App\Http\Criteria;
use App\Http\PaginatedCollection;
use App\Sale\Dao\SaleDao;
use App\Sale\Exception\SaleCsvOpenException;
use App\Sale\Exception\SaleCsvReadException;
use App\Sale\Mapper\SaleMapper;

final class SaleReadService {
    public function __construct(
        private SaleDao $saleDao,
        private SaleMapper $mapper,
    ) {}

    public function list(Criteria $criteria): PaginatedCollection {
        $page = $this->saleDao->list($criteria);
        return new PaginatedCollection(
            array_map([$this->mapper, 'map'], $page->items),
            $page->total,
            $page->page,
            $page->perPage
        );
    }

    public function totals(Criteria $criteria): array {
        $t = $this->saleDao->totals($criteria);
        return [
            'matching_sales' => (int) $t['total'],
            'approved' => (int) $t['approved_count'],
            'canceled' => (int) $t['canceled_count'],
            'gross_value' => round((float) $t['gross_value'], 2),
        ];
    }

    public function export(Criteria $criteria): string {
        return $this->buildCsvFromRows($this->saleDao->export($criteria));
    }

    private function buildCsvFromRows(array $rows): string {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new SaleCsvOpenException();
        }

        fputcsv($handle, ['external_id', 'campaign_id', 'seller_id', 'product_id', 'quantity', 'unit_value']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                $row['external_id'],
                (int) $row['campaign_id'],
                (int) $row['seller_id'],
                (int) $row['product_id'],
                (int) $row['quantity'],
                $row['unit_value'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        if ($csv === false) {
            throw new SaleCsvReadException();
        }

        return $csv;
    }
}
