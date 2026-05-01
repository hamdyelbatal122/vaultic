<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuthenticationFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public string $reason,
        public ?string $credentialId = null,
        public ?string $userIdentifier = null,
    ) {
    }
}
