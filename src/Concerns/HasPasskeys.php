<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Hamzi\Vaultic\Models\Passkey;

trait HasPasskeys
{
    public function passkeys(): HasMany
    {
        return $this->hasMany(Passkey::class);
    }
}
