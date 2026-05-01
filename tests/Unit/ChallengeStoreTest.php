<?php

namespace Hamzi\Vaultic\Tests\Unit;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Hamzi\Vaultic\Services\ChallengeStore;
use Hamzi\Vaultic\Tests\TestCase;

class ChallengeStoreTest extends TestCase
{
    public function test_it_issues_and_pulls_single_use_challenge()
    {
        $store = new ChallengeStore(
            new Repository(new ArrayStore()),
            'vaultic:test:',
            60
        );

        $challenge = $store->issue('authenticate', 'user@example.com');

        $this->assertNotEmpty($challenge);
        $this->assertSame($challenge, $store->pull('authenticate', 'user@example.com'));
        $this->assertNull($store->pull('authenticate', 'user@example.com'));
    }
}
