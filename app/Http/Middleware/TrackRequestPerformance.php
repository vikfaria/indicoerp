<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TrackRequestPerformance
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('performance.enabled')) {
            return $next($request);
        }

        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        $startedAt = microtime(true);
        $response = $next($request);
        $durationMs = (microtime(true) - $startedAt) * 1000;
        $status = $response->getStatusCode();
        $threshold = (int) config('performance.slow_request_ms', 1200);

        if ($durationMs < $threshold && $status < 500) {
            return $response;
        }

        $logLevel = $status >= 500 ? 'error' : 'warning';
        $user = Auth::user();

        Log::channel('performance')->{$logLevel}('Slow request detected', [
            'method' => $request->getMethod(),
            'path' => $request->path(),
            'route_name' => optional($request->route())->getName(),
            'status' => $status,
            'duration_ms' => round($durationMs, 2),
            'threshold_ms' => $threshold,
            'ip' => $request->ip(),
            'user_id' => $user?->id,
            'created_by' => $user?->created_by,
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'user_agent' => substr((string) $request->userAgent(), 0, 180),
        ]);

        return $response;
    }

    private function shouldSkip(Request $request): bool
    {
        $path = trim($request->path(), '/');
        $ignoredPrefixes = (array) config('performance.request_ignore_prefixes', []);

        foreach ($ignoredPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, trim((string) $prefix, '/') . '/')) {
                return true;
            }
        }

        return false;
    }
}
