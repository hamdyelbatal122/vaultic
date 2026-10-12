<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Jobs;

use Hamzi\Vaultic\Contracts\PasskeyRepository;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Updates passkey sign count and last used metadata in the background.
 */
class RecordPasskeyUsageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $credentialId,
        public readonly int $signCount,
        public readonly ?string $clientIp = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PasskeyRepository $repository): void
    {
        try {
            $passkey = $repository->findByCredentialId($this->credentialId);

            if ($passkey !== null) {
                $repository->markAsUsed($passkey, $this->signCount, $this->clientIp);
            }
        } catch (Throwable) {
            // Suppress error to avoid failing queue workers on transient issues.
        }
    }
}
