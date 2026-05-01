<?php

namespace Hamzi\Vaultic\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Hamzi\Vaultic\Models\Passkey;

class PasskeyRegistered
{
    use Dispatchable;
    use SerializesModels;

    /** @var mixed */
    public $user;

    /** @var Passkey */
    public $passkey;

    /**
     * @param mixed $user
     * @param Passkey $passkey
     */
    public function __construct($user, Passkey $passkey)
    {
        $this->user = $user;
        $this->passkey = $passkey;
    }
}
