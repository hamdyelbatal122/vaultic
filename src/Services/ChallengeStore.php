<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;

final class ChallengeStore
{
    public function __construct(
        private readonly Repository $cache,
        private readonly string $prefix,
        private readonly int $ttlSeconds,
    ) {
    }

    public function issue(string $scope, string $subject): string
    {
        $challenge = Str::random(64);
        $this->cache->put($this->key($scope, $subject), $challenge, now()->addSeconds($this->ttlSeconds));

        return $challenge;
    }

    public function pull(string $scope, string $subject): ?string
    {
        $key = $this->key($scope, $subject);
        $value = $this->cache->get($key);
        $this->cache->forget($key);

        return is_string($value) ? $value : null;
    }

    private function key(string $scope, string $subject): string
    {
        return $this->prefix.$scope.':'.hash('sha256', $subject);
    }
}
