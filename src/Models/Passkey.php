<?php

namespace Hamzi\Vaultic\Models;

use Illuminate\Database\Eloquent\Model;

class Passkey extends Model
{
    protected $table = 'passkeys';

    protected $hidden = [
        'public_key',
    ];

    protected $fillable = [
        'user_id',
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

    public function user()
    {
        return $this->belongsTo(config('vaultic.user_model', config('auth.providers.users.model')));
    }
}
