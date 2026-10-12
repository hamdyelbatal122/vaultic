<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Jobs;

use Carbon\Carbon;
use Hamzi\Vaultic\Models\Passkey;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Prunes orphaned passkey records whose authenticatable model no longer exists.
 */
class PruneOrphanedPasskeysJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly ?int $inactiveDays = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Passkey::query()->chunkById(100, function ($passkeys): void {
                foreach ($passkeys as $passkey) {
                    if ($passkey->authenticatable === null) {
                        $passkey->delete();
                    }
                }
            });

            if ($this->inactiveDays !== null && $this->inactiveDays > 0) {
                Passkey::query()
                    ->whereNotNull('last_used_at')
                    ->where('last_used_at', '<', Carbon::now()->subDays($this->inactiveDays))
                    ->delete();
            }
        } catch (Throwable) {
            // Suppress error to avoid failing queue workers.
        }
    }
}
