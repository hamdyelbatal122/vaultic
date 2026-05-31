<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Tests\Feature;

use Hamzi\Vaultic\Tests\TestCase;

class PasskeyRoutesTest extends TestCase
{
    public function test_it_registers_passkey_routes(): void
    {
        $registerOptionsPath = route('vaultic.register.options', [], false);
        $authenticatePath = route('vaultic.authenticate.store', [], false);
        $destroyPath = route('vaultic.passkeys.destroy', ['passkey' => 1], false);
        $renamePath = route('vaultic.passkeys.rename', ['passkey' => 1], false);
        $apiRegisterOptionsPath = route('vaultic.api.register.options', [], false);
        $apiAuthenticatePath = route('vaultic.api.authenticate.store', [], false);

        $this->assertStringContainsString('/passkeys/register/options', $registerOptionsPath);
        $this->assertStringContainsString('/passkeys/authenticate', $authenticatePath);
        $this->assertStringContainsString('/passkeys/1', $destroyPath);
        $this->assertStringContainsString('/passkeys/1', $renamePath);
        $this->assertStringContainsString('/api/passkeys/register/options', $apiRegisterOptionsPath);
        $this->assertStringContainsString('/api/passkeys/authenticate', $apiAuthenticatePath);
    }
}
