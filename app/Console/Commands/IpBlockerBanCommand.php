<?php

namespace App\Console\Commands;

use App\Support\BannedIps;
use Illuminate\Console\Command;

class IpBlockerBanCommand extends Command
{
    protected $signature = 'ip-blocker:ban {ip} {--hours=24}';

    protected $description = 'Manually block an IP address';

    public function handle(): int
    {
        $ip = $this->argument('ip');
        $hours = (float) $this->option('hours');

        BannedIps::ban($ip, (int) ($hours * 3600), 'manual');

        $this->info("Blocked {$ip} for {$hours}h.");

        return self::SUCCESS;
    }
}
