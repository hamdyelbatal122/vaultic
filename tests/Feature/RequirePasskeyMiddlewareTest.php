<?php

namespace Hamzi\Vaultic\Tests\Feature;

use Illuminate\Support\Facades\Route;
use Hamzi\Vaultic\Tests\TestCase;

class RequirePasskeyMiddlewareTest extends TestCase
{
    public function test_it_blocks_sensitive_routes_when_session_is_missing()
    {
        Route::middleware(['web', 'passkey.required'])->get('/_vaultic/protected', function () {
            return 'ok';
        });

        $this->get('/_vaultic/protected')->assertStatus(403);
    }

    public function test_it_allows_sensitive_routes_when_session_flag_exists()
    {
        Route::middleware(['web', 'passkey.required'])->get('/_vaultic/allowed', function () {
            return 'ok';
        });

        $this->withSession(['vaultic.passkeys.authenticated' => true])
            ->get('/_vaultic/allowed')
            ->assertStatus(200)
            ->assertSee('ok');
    }
}
