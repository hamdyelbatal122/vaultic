<?php

namespace Hamzi\Vaultic\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Hamzi\Vaultic\Models\Passkey;

trait HasPasskeys
{
    public function passkeys()
    {
        return $this->hasMany(Passkey::class);
    }
}
