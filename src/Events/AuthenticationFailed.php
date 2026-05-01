<?php

namespace Hamzi\Vaultic\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuthenticationFailed
{
    use Dispatchable;
    use SerializesModels;

    /** @var string */
    public $reason;

    /** @var string|null */
    public $credentialId;

    /** @var string|null */
    public $userIdentifier;

    /**
     * @param string $reason
     * @param string|null $credentialId
     * @param string|null $userIdentifier
     */
    public function __construct($reason, $credentialId = null, $userIdentifier = null)
    {
        $this->reason = $reason;
        $this->credentialId = $credentialId;
        $this->userIdentifier = $userIdentifier;
    }
}
