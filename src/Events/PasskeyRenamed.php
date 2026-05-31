<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Hamzi\Vaultic\Models\Passkey;

/**
 * Dispatched when a passkey is successfully renamed.
 */
class PasskeyRenamed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly mixed $user,
        public readonly Passkey $passkey,
        public readonly string $oldName,
        public readonly string $newName,
    ) {
    }
}
