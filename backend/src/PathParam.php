<?php
declare(strict_types=1);

namespace App;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class PathParam {
    public function __construct(public readonly string $name) {}
}
