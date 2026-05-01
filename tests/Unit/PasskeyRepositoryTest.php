<?php

namespace Hamzi\Vaultic\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Repositories\EloquentPasskeyRepository;
use Hamzi\Vaultic\Tests\Fixtures\AdminUser;
use Hamzi\Vaultic\Tests\Fixtures\TestUser;
use Hamzi\Vaultic\Tests\TestCase;

class PasskeyRepositoryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
        });

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
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
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function test_it_lists_passkeys_for_the_requested_authenticatable_only()
    {
        $user = TestUser::query()->create(['email' => 'user@example.com']);
        $admin = AdminUser::query()->create(['email' => 'admin@example.com']);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Laptop',
            'credential_id' => 'cred-user',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        Passkey::query()->create([
            'authenticatable_type' => AdminUser::class,
            'authenticatable_id' => (string) $admin->getAuthIdentifier(),
            'name' => 'Security key',
            'credential_id' => 'cred-admin',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        $repository = new EloquentPasskeyRepository();
        $passkeys = $repository->listForAuthenticatable($user);

        $this->assertCount(1, $passkeys);
        $this->assertSame('cred-user', $passkeys->first()->credential_id);
    }

    public function test_it_only_deletes_a_passkey_owned_by_the_requested_authenticatable()
    {
        $user = TestUser::query()->create(['email' => 'user@example.com']);
        $admin = AdminUser::query()->create(['email' => 'admin@example.com']);

        $ownedPasskey = Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Laptop',
            'credential_id' => 'cred-owned',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        $foreignPasskey = Passkey::query()->create([
            'authenticatable_type' => AdminUser::class,
            'authenticatable_id' => (string) $admin->getAuthIdentifier(),
            'name' => 'Security key',
            'credential_id' => 'cred-foreign',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        $repository = new EloquentPasskeyRepository();

        $this->assertTrue($repository->deleteForAuthenticatable($user, $ownedPasskey));
        $this->assertFalse($repository->deleteForAuthenticatable($user, $foreignPasskey));
        $this->assertDatabaseMissing('passkeys', ['credential_id' => 'cred-owned']);
        $this->assertDatabaseHas('passkeys', ['credential_id' => 'cred-foreign']);
    }

    public function test_it_tracks_last_usage_timestamp_and_ip_address()
    {
        $user = TestUser::query()->create(['email' => 'user@example.com']);

        $passkey = Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Laptop',
            'credential_id' => 'cred-usage',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        $repository = new EloquentPasskeyRepository();
        $repository->markAsUsed($passkey, 3, '2001:db8::1');

        $passkey->refresh();

        $this->assertSame(3, $passkey->sign_count);
        $this->assertNotNull($passkey->last_used_at);
        $this->assertSame('2001:db8::1', $passkey->last_used_ip);
    }
}