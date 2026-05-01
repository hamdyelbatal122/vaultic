<?php

namespace Hamzi\Vaultic\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Hamzi\Vaultic\Models\Passkey;

trait HasPasskeys
{
    public function passkeys(): MorphMany
    {
        return $this->morphMany(Passkey::class, 'authenticatable');
    }
}
