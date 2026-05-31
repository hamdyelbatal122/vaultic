<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Services;

use Illuminate\Contracts\Cache\Repository;
use Carbon\Carbon;

/**
 * Manages single-use WebAuthn challenges using the cache store.
 *
 * Challenges are scoped (register / authenticate) and keyed by a subject
 * identifier. Each challenge can only be consumed once via pull().
 */
class ChallengeStore
{
    public function __construct(
        private readonly Repository $cache,
        private readonly string $prefix,
        private readonly int $ttlSeconds,
    ) {
    }

    /**
     * Issue a new single-use challenge.
     *
     * @param  string  $scope    The operation scope (e.g. 'register', 'authenticate').
     * @param  string  $subject  The subject identifier (user ID, email, or discoverable key).
     * @return string  The hex-encoded challenge string.
     */
    public function issue(string $scope, string $subject): string
    {
        $challenge = bin2hex(random_bytes(32));

        $this->cache->put(
            $this->key($scope, $subject),
            $challenge,
            Carbon::now()->addSeconds($this->ttlSeconds),
        );

        return $challenge;
    }

    /**
     * Pull (consume) a previously issued challenge.
     *
     * Returns the challenge string if it exists and has not expired,
     * then immediately deletes it from the store to enforce single-use.
     *
     * @param  string  $scope
     * @param  string  $subject
     * @return string|null
     */
    public function pull(string $scope, string $subject): ?string
    {
        $key = $this->key($scope, $subject);
        $value = $this->cache->get($key);
        $this->cache->forget($key);

        return is_string($value) ? $value : null;
    }

    /**
     * Build the cache key for a given scope and subject.
     */
    private function key(string $scope, string $subject): string
    {
        return $this->prefix . $scope . ':' . hash('sha256', $subject);
    }
}
