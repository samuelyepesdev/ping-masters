<?php

namespace App\Console\Commands;

use App\Support\BannedIps;
use Illuminate\Console\Command;

class IpBlockerListCommand extends Command
{
    protected $signature = 'ip-blocker:list';

    protected $description = 'List IPs currently blocked by the IP blocker';

    public function handle(): int
    {
        $active = BannedIps::active();

        if ($active === []) {
            $this->info('No IPs are currently blocked.');

            return self::SUCCESS;
        }

        $this->table(
            ['IP', 'Reason', 'Banned at', 'Expires at'],
            collect($active)->map(fn (array $meta, string $ip) => [
                $ip, $meta['reason'], $meta['banned_at'], $meta['expires_at'],
            ])->all()
        );

        return self::SUCCESS;
    }
}
