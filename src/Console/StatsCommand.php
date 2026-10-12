<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Console;

use Carbon\Carbon;
use Hamzi\Vaultic\Models\Passkey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnostic command to display passkey metrics and distribution.
 */
class StatsCommand extends Command
{
    /** @var string */
    protected $signature = 'vaultic:stats';

    /** @var string */
    protected $description = 'Display passkey usage metrics and authenticator statistics.';

    public function handle(): int
    {
        if (! Schema::hasTable('passkeys')) {
            $this->error('The [passkeys] table does not exist. Run migrations first.');

            return self::FAILURE;
        }

        $now = Carbon::now();
        $totalPasskeys = Passkey::query()->count();
        $totalOwners = Passkey::query()
            ->distinct()
            ->count('authenticatable_id');

        $usedLast24h = Passkey::query()
            ->whereNotNull('last_used_at')
            ->where('last_used_at', '>=', (clone $now)->subHours(24))
            ->count();

        $usedLast7d = Passkey::query()
            ->whereNotNull('last_used_at')
            ->where('last_used_at', '>=', (clone $now)->subDays(7))
            ->count();

        $usedLast30d = Passkey::query()
            ->whereNotNull('last_used_at')
            ->where('last_used_at', '>=', (clone $now)->subDays(30))
            ->count();

        $neverUsed = Passkey::query()
            ->whereNull('last_used_at')
            ->count();

        $this->info('Vaultic Passkey Statistics');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Registered Passkeys', (string) $totalPasskeys],
                ['Distinct Authenticatable Users', (string) $totalOwners],
                ['Active within last 24 hours', (string) $usedLast24h],
                ['Active within last 7 days', (string) $usedLast7d],
                ['Active within last 30 days', (string) $usedLast30d],
                ['Never Used', (string) $neverUsed],
            ],
        );

        $transports = Passkey::query()
            ->whereNotNull('transports')
            ->pluck('transports');

        $transportCounts = [];
        foreach ($transports as $list) {
            if (is_array($list)) {
                foreach ($list as $item) {
                    if (is_string($item)) {
                        $transportCounts[$item] = ($transportCounts[$item] ?? 0) + 1;
                    }
                }
            }
        }

        if (! empty($transportCounts)) {
            $this->newLine();
            $this->info('Authenticator Transports');
            $transportRows = [];
            foreach ($transportCounts as $transport => $count) {
                $transportRows[] = [$transport, (string) $count];
            }
            $this->table(['Transport', 'Count'], $transportRows);
        }

        return self::SUCCESS;
    }
}
