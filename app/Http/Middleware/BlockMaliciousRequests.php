<?php

namespace App\Http\Middleware;

use App\Support\BannedIps;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class BlockMaliciousRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        // Railway's healthcheck polls this constantly from its internal network;
        // never let it trip the rate limit or get banned.
        if ($request->is('up')) {
            return $next($request);
        }

        $ip = $request->ip();

        if (BannedIps::isBanned($ip)) {
            abort(403);
        }

        if ($this->matchesBannedPath($request->path())) {
            BannedIps::ban($ip, config('ip-blocker.ban_seconds'), 'suspicious path: '.$request->path());
            abort(403);
        }

        if ($this->exceedsRateLimit($ip)) {
            BannedIps::ban($ip, config('ip-blocker.ban_seconds'), 'rate limit exceeded');
            abort(429);
        }

        return $next($request);
    }

    private function matchesBannedPath(string $path): bool
    {
        $path = strtolower($path);

        if (in_array($path, config('ip-blocker.banned_exact_paths'), true)) {
            return true;
        }

        foreach (config('ip-blocker.banned_path_needles') as $needle) {
            if (Str::contains($path, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function exceedsRateLimit(string $ip): bool
    {
        $key = "ip-blocker:hits:{$ip}";
        $window = config('ip-blocker.window_seconds');

        $hits = Cache::add($key, 1, $window) ? 1 : Cache::increment($key);

        return $hits > config('ip-blocker.max_requests');
    }
}
