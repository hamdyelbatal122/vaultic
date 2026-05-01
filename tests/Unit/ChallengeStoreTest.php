<?php

declare(strict_types=1);

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Hamzi\Vaultic\Services\ChallengeStore;

it('issues and pulls a single use challenge', function (): void {
    $store = new ChallengeStore(
        cache: new Repository(new ArrayStore()),
        prefix: 'vaultic:test:',
        ttlSeconds: 60,
    );

    $challenge = $store->issue('authenticate', 'user@example.com');

    expect($challenge)->toBeString()->not->toBe('');
    expect($store->pull('authenticate', 'user@example.com'))->toBe($challenge);
    expect($store->pull('authenticate', 'user@example.com'))->toBeNull();
});
