<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Passkey extends Model
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('vaultic.user_model', config('auth.providers.users.model')));
    }
}
