<?php

namespace Hamzi\Vaultic\Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Tests\Fixtures\TestUser;
use Hamzi\Vaultic\Tests\TestCase;

class PasskeyManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('passkeys', function (Blueprint $table) {
            $table->id();
            $table->string('authenticatable_type');
            $table->string('authenticatable_id', 191);
            $table->string('name')->default('Unnamed device');
            $table->string('credential_id')->unique();
            $table->longText('public_key');
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->string('transports')->nullable();
            $table->string('aaguid', 36)->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_renders_the_passkey_button_helper()
    {
        $html = Blade::render('{{ vaultic_passkey_button(["identifierSelector" => "#email"]) }}');

        $this->assertStringContainsString('data-vaultic-passkey', $html);
        $this->assertStringContainsString('FIDO2/WebAuthn Login', $html);
        $this->assertStringContainsString('#email', $html);
    }

    public function test_it_deletes_an_owned_passkey()
    {
        $user = TestUser::query()->create([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);

        $passkey = Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Laptop',
            'credential_id' => 'cred-delete',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        $this->actingAs($user)
            ->delete(route('vaultic.passkeys.destroy', $passkey))
            ->assertRedirect();

        $this->assertDatabaseMissing('passkeys', ['credential_id' => 'cred-delete']);
    }
}