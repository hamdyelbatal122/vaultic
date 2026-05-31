<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Represents a WebAuthn/Passkey credential linked to an authenticatable model.
 *
 * @property int         $id
 * @property string      $authenticatable_type
 * @property string      $authenticatable_id
 * @property string      $name
 * @property string      $credential_id
 * @property string      $public_key
 * @property int         $sign_count
 * @property array|null  $transports
 * @property string|null $aaguid
 * @property \Carbon\Carbon|null $last_used_at
 * @property string|null $last_used_ip
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 * @property-read Model  $authenticatable
 */
class Passkey extends Model
{
    /** @var string */
    protected $table = 'passkeys';

    /** @var list<string> */
    protected $hidden = [
        'public_key',
    ];

    /** @var list<string> */
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
        'last_used_ip',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sign_count'   => 'integer',
            'transports'   => 'json',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * The owning authenticatable model (polymorphic).
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Alias for the authenticatable relationship.
     */
    public function user(): MorphTo
    {
        return $this->authenticatable();
    }
}
