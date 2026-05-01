<?php

namespace Hamzi\Vaultic\Services;

use Illuminate\Contracts\Cache\Repository;
use Carbon\Carbon;

class ChallengeStore
{
    /** @var Repository */
    private $cache;

    /** @var string */
    private $prefix;

    /** @var int */
    private $ttlSeconds;

    /**
     * @param Repository $cache
     * @param string $prefix
     * @param int $ttlSeconds
     */
    public function __construct(Repository $cache, $prefix, $ttlSeconds)
    {
        $this->cache = $cache;
        $this->prefix = $prefix;
        $this->ttlSeconds = $ttlSeconds;
    }

    /**
     * @param string $scope
     * @param string $subject
     * @return string
     */
    public function issue($scope, $subject)
    {
        $challenge = bin2hex(random_bytes(32));
        $this->cache->put(
            $this->key($scope, $subject),
            $challenge,
            Carbon::now()->addSeconds($this->ttlSeconds)
        );

        return $challenge;
    }

    /**
     * @param string $scope
     * @param string $subject
     * @return string|null
     */
    public function pull($scope, $subject)
    {
        $key = $this->key($scope, $subject);
        $value = $this->cache->get($key);
        $this->cache->forget($key);

        return is_string($value) ? $value : null;
    }

    /**
     * @param string $scope
     * @param string $subject
     * @return string
     */
    private function key($scope, $subject)
    {
        return $this->prefix.$scope.':'.hash('sha256', $subject);
    }
}
