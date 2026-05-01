<?php

namespace Hamzi\Vaultic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Passkey extends Model
{
    protected $table = 'passkeys';

    protected $hidden = [
        'public_key',
    ];

    protected $fillable = [
        'authenticatable_type',
        'authenticatable_id',
        'name',
        'credential_id',
        'public_key',
        'sign_count',
        'transports',
        'aaguid',
        'last_used_at',
    ];

    protected $casts = [
        'sign_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): MorphTo
    {
        return $this->authenticatable();
    }
}
