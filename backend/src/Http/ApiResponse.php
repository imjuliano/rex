<?php
declare(strict_types=1);

namespace App\Http;

/**
 * The single place that decides what a successful response looks like.
 *
 * Shape is always { data, meta } and, for collections, { data, meta, links }.
 * Errors keep their own envelope in ExceptionHandler, distinguished by the
 * presence of "error" instead of "data".
 */
final class ApiResponse {
    /**
     * A single resource.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed> $meta
     */
    public static function item(array $data, int $status = 200, array $meta = []): void {
        $payload = ['data' => $data];
        if ($meta !== []) {
            $payload['meta'] = $meta;
        }
        self::emit($status, $payload);
    }

    /**
     * A page of resources, with navigation links.
     *
     * @param array<string, mixed> $extraMeta Resource-level aggregates (e.g. wallet totals).
     * @param array<string, mixed> $query Original query params, echoed into links.
     */
    public static function collection(
        PaginatedCollection $page,
        string $path,
        array $query = [],
        array $extraMeta = [],
        int $status = 200
    ): void {
        $meta = [
            'page' => $page->page,
            'per_page' => $page->perPage,
            'count' => $page->count(),
            'total' => $page->total,
            'total_pages' => $page->totalPages(),
        ];

        self::emit($status, [
            'data' => $page->items,
            'meta' => $extraMeta === [] ? $meta : $meta + ['summary' => $extraMeta],
            'links' => self::links($page, $path, $query),
        ]);
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, ?string>
     */
    private static function links(PaginatedCollection $page, string $path, array $query): array {
        $last = max(1, $page->totalPages());
        $url = function (int $p) use ($path, $query): string {
            $query['page'] = $p;
            $query['per_page'] = $query['per_page'] ?? null;
            return $path . '?' . http_build_query(array_filter($query, fn($v) => $v !== null && $v !== ''));
        };

        return [
            'self' => $url($page->page),
            'first' => $url(1),
            'prev' => $page->page > 1 ? $url($page->page - 1) : null,
            'next' => $page->page < $last ? $url($page->page + 1) : null,
            'last' => $url($last),
        ];
    }

    /** @param array<string, mixed> $payload */
    private static function emit(int $status, array $payload): void {
        http_response_code($status);
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
