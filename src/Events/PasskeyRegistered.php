<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Hamzi\Vaultic\Models\Passkey;

class PasskeyRegistered
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public mixed $user,
        public Passkey $passkey,
    ) {
    }
}
