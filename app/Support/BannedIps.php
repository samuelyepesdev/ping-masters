<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;

class BannedIps
{
    private const INDEX_KEY = 'ip-blocker:index';

    public static function isBanned(string $ip): bool
    {
        return Cache::has(self::banKey($ip));
    }

    public static function ban(string $ip, int $seconds, string $reason = 'manual'): void
    {
        Cache::put(self::banKey($ip), true, $seconds);

        $index = Cache::get(self::INDEX_KEY, []);
        $index[$ip] = [
            'reason' => $reason,
            'banned_at' => now()->toDateTimeString(),
            'expires_at' => now()->addSeconds($seconds)->toDateTimeString(),
        ];
        Cache::forever(self::INDEX_KEY, $index);
    }

    public static function unban(string $ip): bool
    {
        $existed = Cache::forget(self::banKey($ip));

        $index = Cache::get(self::INDEX_KEY, []);
        unset($index[$ip]);
        Cache::forever(self::INDEX_KEY, $index);

        return $existed;
    }

    /**
     * Currently banned IPs, pruning any entries whose ban already expired.
     *
     * @return array<string, array{reason: string, banned_at: string, expires_at: string}>
     */
    public static function active(): array
    {
        $index = Cache::get(self::INDEX_KEY, []);

        $active = [];
        foreach ($index as $ip => $meta) {
            if (self::isBanned($ip)) {
                $active[$ip] = $meta;
            }
        }

        if (count($active) !== count($index)) {
            Cache::forever(self::INDEX_KEY, $active);
        }

        return $active;
    }

    private static function banKey(string $ip): string
    {
        return "ip-blocker:banned:{$ip}";
    }
}
