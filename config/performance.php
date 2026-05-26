<?php

return [
    'enabled' => (bool) env('PERFORMANCE_MONITORING_ENABLED', false),

    // Log a request when duration is above this value (milliseconds).
    'slow_request_ms' => (int) env('PERF_SLOW_REQUEST_MS', 1200),

    // Log an individual query when execution time is above this value (milliseconds).
    'slow_query_ms' => (int) env('PERF_SLOW_QUERY_MS', 800),

    // Log when the cumulative query time in a request crosses this value (milliseconds).
    'slow_query_total_ms' => (int) env('PERF_SLOW_QUERY_TOTAL_MS', 1500),

    // Cache TTL for account dashboards (seconds).
    'dashboard_cache_ttl_seconds' => (int) env('PERF_DASHBOARD_CACHE_TTL_SECONDS', 120),

    // Cache TTL for account report payloads (seconds).
    'report_cache_ttl_seconds' => (int) env('PERF_REPORT_CACHE_TTL_SECONDS', 120),

    // Paths ignored by request performance logging.
    'request_ignore_prefixes' => [
        'up',
        '_debugbar',
        'telescope',
    ],
];
