<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Events;

use Hamzi\Vaultic\Models\Passkey;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Dispatched when a new passkey is successfully registered.
 */
class PasskeyRegistered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly mixed $user,
        public readonly Passkey $passkey,
    ) {}
}
