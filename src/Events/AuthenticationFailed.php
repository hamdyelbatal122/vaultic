<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AuthenticationFailed
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $reason,
        public readonly ?string $credentialId = null,
        public readonly ?string $userIdentifier = null,
    ) {
    }
}
