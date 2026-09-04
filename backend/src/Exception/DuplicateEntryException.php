<?php
declare(strict_types=1);

namespace App\Exception;

use Throwable;

final class DuplicateEntryException extends ConflictException {
    public function __construct(?Throwable $previous = null) {
        parent::__construct(
            ErrorCode::DUPLICATE_ENTRY,
            'A record with these unique values already exists.',
            [],
            $previous
        );
    }
}
