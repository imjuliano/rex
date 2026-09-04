<?php
declare(strict_types=1);

namespace App\Sale\Dto;

use App\Sale\Exception\SaleBatchMissingException;
use App\Sale\Exception\SaleBatchRowInvalidException;

final class BatchSalesDto {
    /** @var list<CreateSaleDto> */
    public readonly array $sales;

    public function __construct(array $sales) {
        $this->sales = $sales;
    }

    public static function fromArray(array $body): self {
        if (!array_key_exists('sales', $body) || !is_array($body['sales']) || $body['sales'] === []) {
            throw new SaleBatchMissingException();
        }

        $sales = [];
        foreach ($body['sales'] as $idx => $row) {
            if (!is_array($row)) {
                throw new SaleBatchRowInvalidException($idx);
            }
            $sales[] = CreateSaleDto::fromArray($row);
        }

        return new self($sales);
    }
}
