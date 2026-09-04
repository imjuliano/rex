<?php
declare(strict_types=1);

namespace App\Sale\Controller;

use App\Exception\ValidationException;
use App\Http\ApiResponse;
use App\Http\QueryParams;
use App\Sale\Dto\BatchSalesDto;
use App\Sale\Dto\CreateSaleDto;
use App\Sale\Filter\SaleFilter;
use App\Sale\Service\SaleReadService;
use App\Sale\Service\SaleWriteService;
use App\Validation\Assert;
use App\Validation\Limits;

final class SaleController {
    public function __construct(
        private SaleReadService $reads,
        private SaleWriteService $writes,
    ) {}

    public function list(QueryParams $query): void {
        $criteria = SaleFilter::from($query);
        $page = $this->reads->list($criteria);
        $totals = $this->reads->totals($criteria);
        ApiResponse::collection($page, '/sales', $query->all(), $totals);
    }

    public function create(array $body, array $actor): void {
        $sale = $this->writes->create(CreateSaleDto::fromArray($body), $actor);
        ApiResponse::item($sale, 201);
    }

    public function batch(array $body, array $actor): void {
        $summary = $this->writes->batch(BatchSalesDto::fromArray($body), $actor);
        $status = $summary['created'] === $summary['submitted'] ? 200 : 207;

        ApiResponse::item(
            ['results' => $summary['results']],
            $status,
            [
                'submitted' => $summary['submitted'],
                'created' => $summary['created'],
                'skipped' => $summary['skipped'],
                'errors' => $summary['errors'],
                'points_credited' => $summary['points_credited'],
            ]
        );
    }

    public function export(QueryParams $query): void {
        if ($query->int('seller_id') === null) {
            throw ValidationException::missingField('seller_id');
        }

        $criteria = SaleFilter::from($query);
        $csv = $this->reads->export($criteria);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sales.csv"');
        echo $csv;
    }

    public function cancel(string $externalId, array $actor): void {
        $externalId = Assert::nonEmptyString($externalId, 'external_id', Limits::SALE_EXTERNAL_ID);
        $sale = $this->writes->cancel($externalId, $actor);
        ApiResponse::item($sale);
    }
}
