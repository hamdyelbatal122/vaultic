<?php

namespace Hamzi\Vaultic\Tests\Feature;

use Hamzi\Vaultic\Tests\TestCase;

class PasskeyRoutesTest extends TestCase
{
    public function test_it_registers_passkey_routes()
    {
        $registerOptionsPath = route('vaultic.register.options', [], false);
        $authenticatePath = route('vaultic.authenticate.store', [], false);

        $this->assertContains('/passkeys/register/options', $registerOptionsPath);
        $this->assertContains('/passkeys/authenticate', $authenticatePath);
    }
}
