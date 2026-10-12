<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Represents a WebAuthn/Passkey credential linked to an authenticatable model.
 *
 * @property int $id
 * @property string $authenticatable_type
 * @property string $authenticatable_id
 * @property string $name
 * @property string $credential_id
 * @property string $public_key
 * @property int $sign_count
 * @property list<string>|null $transports
 * @property string|null $aaguid
 * @property CarbonInterface|null $last_used_at
 * @property string|null $last_used_ip
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Model $authenticatable
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
            'sign_count' => 'integer',
            'transports' => 'json',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * The owning authenticatable model (polymorphic).
     *
     * @return MorphTo<Model, $this>
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Alias for the authenticatable relationship.
     *
     * @return MorphTo<Model, $this>
     */
    public function user(): MorphTo
    {
        return $this->authenticatable();
    }

    /**
     * Determine whether this passkey has ever been used.
     */
    public function isUsed(): bool
    {
        return $this->last_used_at !== null;
    }

    /**
     * Get the number of days elapsed since the passkey was last used.
     */
    public function daysSinceLastUse(): ?int
    {
        if ($this->last_used_at === null) {
            return null;
        }

        return abs((int) Carbon::now()->diffInDays($this->last_used_at));
    }

    /**
     * Scope query to passkeys used within the given number of days.
     *
     * @param  Builder<Passkey>  $query
     * @return Builder<Passkey>
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        /** @var Builder<Passkey> $scoped */
        $scoped = $query->whereNotNull('last_used_at')
            ->where('last_used_at', '>=', Carbon::now()->subDays($days));

        return $scoped;
    }

    /**
     * Scope query to passkeys that have never been used.
     *
     * @param  Builder<Passkey>  $query
     * @return Builder<Passkey>
     */
    public function scopeUnused(Builder $query): Builder
    {
        /** @var Builder<Passkey> $scoped */
        $scoped = $query->whereNull('last_used_at');

        return $scoped;
    }

    /**
     * Scope query by authenticator AAGUID.
     *
     * @param  Builder<Passkey>  $query
     * @return Builder<Passkey>
     */
    public function scopeForAaguid(Builder $query, string $aaguid): Builder
    {
        return $query->where('aaguid', $aaguid);
    }
}
