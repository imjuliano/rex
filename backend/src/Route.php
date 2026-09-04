<?php
declare(strict_types=1);

namespace App;

#[\Attribute(\Attribute::TARGET_METHOD)]
final class Route {
    /**
     * @param string $method HTTP method in uppercase.
     * @param string $path Route pattern, e.g. '/products/{id}'.
     * @param ?list<string> $roles null = public, [] = any authenticated user.
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly ?array $roles = null,
    ) {}
}
