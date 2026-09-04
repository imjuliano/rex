<?php
declare(strict_types=1);

namespace App\Http\Exception;

use App\Exception\ErrorCode;
use App\Exception\NotFoundException;

final class RouteNotFoundException extends NotFoundException {
    public function __construct(string $method, string $path) {
        parent::__construct(
            ErrorCode::ROUTE_NOT_FOUND,
            'The requested endpoint does not exist.',
            ['method' => $method, 'path' => $path]
        );
    }
}
