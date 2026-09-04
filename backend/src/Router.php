<?php
declare(strict_types=1);

namespace App;

use App\Http\Exception\RouteNotFoundException;

class Router {
    /** @var array<string, list<array{regex: string, handler: callable, roles: ?list<string>}>> */
    private array $routes = [];

    /**
     * @param ?list<string> $roles null = public route, [] = any authenticated user.
     */
    public function add(string $method, string $pattern, callable $handler, ?array $roles = null): void {
        $this->routes[strtoupper($method)][] = [
            'regex' => $this->patternToRegex($pattern),
            'handler' => $handler,
            'roles' => $roles,
        ];
    }

    /**
     * @return array{handler: callable, roles: ?list<string>, params: array<string, string>}
     * @throws NotFoundException when no route matches the request.
     */
    public function match(string $method, string $uri): array {
        $method = strtoupper($method);
        $path = rtrim($uri, '/') ?: '/';

        foreach ($this->routes[$method] ?? [] as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                return [
                    'handler' => $route['handler'],
                    'roles' => $route['roles'],
                    'params' => array_map(
                        'rawurldecode',
                        array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY)
                    ),
                ];
            }
        }

        throw new RouteNotFoundException($method, $path);
    }

    private function patternToRegex(string $pattern): string {
        $normalized = rtrim($pattern, '/') ?: '/';
        return '~^' . preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $normalized) . '$~';
    }
}
