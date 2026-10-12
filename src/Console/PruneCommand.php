<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Console;

use Carbon\Carbon;
use Hamzi\Vaultic\Models\Passkey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Maintenance command to prune orphaned or inactive passkeys.
 */
class PruneCommand extends Command
{
    /** @var string */
    protected $signature = 'vaultic:prune
        {--days= : Delete passkeys unused for more than the specified number of days}
        {--orphaned : Delete passkeys whose authenticatable owner no longer exists}
        {--dry-run : Simulate pruning without executing deletions}';

    /** @var string */
    protected $description = 'Prune orphaned or inactive passkey records.';

    public function handle(): int
    {
        if (! Schema::hasTable('passkeys')) {
            $this->error('The [passkeys] table does not exist. Run migrations first.');

            return self::FAILURE;
        }

        $daysOption = $this->option('days');
        $orphaned = (bool) $this->option('orphaned');
        $dryRun = (bool) $this->option('dry-run');

        if ($daysOption === null && ! $orphaned) {
            $this->error('Please specify either --orphaned or --days= to define pruning criteria.');

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->warn('Executing in dry-run mode. No records will be deleted.');
        }

        $prunedOrphaned = 0;
        if ($orphaned) {
            $orphanIds = [];
            Passkey::query()->chunkById(100, function ($passkeys) use (&$orphanIds): void {
                foreach ($passkeys as $passkey) {
                    if ($passkey->authenticatable === null) {
                        $orphanIds[] = $passkey->id;
                    }
                }
            });

            $prunedOrphaned = count($orphanIds);

            if (! $dryRun && $prunedOrphaned > 0) {
                Passkey::query()->whereIn('id', $orphanIds)->delete();
            }

            $this->info(sprintf(
                'Orphaned passkeys %s: %d',
                $dryRun ? 'found' : 'pruned',
                $prunedOrphaned,
            ));
        }

        $prunedInactive = 0;
        if ($daysOption !== null) {
            $days = max(1, (int) $daysOption);
            $cutoff = Carbon::now()->subDays($days);

            $query = Passkey::query()
                ->whereNotNull('last_used_at')
                ->where('last_used_at', '<', $cutoff);

            $prunedInactive = $query->count();

            if (! $dryRun && $prunedInactive > 0) {
                $query->delete();
            }

            $this->info(sprintf(
                'Inactive passkeys (> %d days) %s: %d',
                $days,
                $dryRun ? 'found' : 'pruned',
                $prunedInactive,
            ));
        }

        $total = $prunedOrphaned + $prunedInactive;
        $this->info(sprintf(
            'Pruning complete. Total records %s: %d',
            $dryRun ? 'identified' : 'removed',
            $total,
        ));

        return self::SUCCESS;
    }
}
