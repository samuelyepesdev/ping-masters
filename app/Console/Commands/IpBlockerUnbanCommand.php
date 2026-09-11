<?php

namespace App\Console\Commands;

use App\Support\BannedIps;
use Illuminate\Console\Command;

class IpBlockerUnbanCommand extends Command
{
    protected $signature = 'ip-blocker:unban {ip}';

    protected $description = 'Remove an IP address from the block list';

    public function handle(): int
    {
        $ip = $this->argument('ip');

        if (BannedIps::unban($ip)) {
            $this->info("Unblocked {$ip}.");
        } else {
            $this->warn("{$ip} was not blocked.");
        }

        return self::SUCCESS;
    }
}
