<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Ban duration
    |--------------------------------------------------------------------------
    |
    | How long an IP stays blocked (403) once it trips either check below.
    |
    */
    'ban_seconds' => (int) env('IP_BLOCKER_BAN_SECONDS', 86400), // 24h

    /*
    |--------------------------------------------------------------------------
    | Rate limit
    |--------------------------------------------------------------------------
    |
    | If an IP makes more than `max_requests` requests within `window_seconds`,
    | it gets a 429 for that request and is then banned for `ban_seconds`.
    |
    */
    'max_requests' => (int) env('IP_BLOCKER_MAX_REQUESTS', 120),
    'window_seconds' => (int) env('IP_BLOCKER_WINDOW_SECONDS', 60),

    /*
    |--------------------------------------------------------------------------
    | Banned path needles
    |--------------------------------------------------------------------------
    |
    | Case-insensitive substrings checked against the request path. A single
    | match instantly bans the IP for `ban_seconds` — this app has no /api/*,
    | WordPress, or admin-panel-style routes, so a hit here is always a
    | vulnerability scanner, never a real user.
    |
    */
    'banned_path_needles' => [
        'api/',
        'wp-admin', 'wp-login', 'wp-json', 'wp-content', 'wordpress', 'xmlrpc.php',
        '.env', '.git/', '.svn/', '.aws/',
        'cgi-bin', 'phpunit', 'eval-stdin', 'vendor/phpunit',
        'actuator',
    ],

    /*
    |--------------------------------------------------------------------------
    | Banned exact paths
    |--------------------------------------------------------------------------
    |
    | Exact (case-insensitive) path matches, checked without wildcards so
    | short generic words don't accidentally shadow a real route.
    |
    */
    'banned_exact_paths' => [
        'admin', 'admin/config', 'debug', 'run', 'system',
    ],

];
