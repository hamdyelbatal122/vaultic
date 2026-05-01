<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Hamzi\Vaultic\Models\Passkey;

final class PasskeyRegistered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly mixed $user,
        public readonly Passkey $passkey,
    ) {
    }
}
