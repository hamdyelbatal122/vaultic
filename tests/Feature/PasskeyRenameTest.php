<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Tests\Fixtures\AdminUser;
use Hamzi\Vaultic\Tests\Fixtures\TestUser;
use Hamzi\Vaultic\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Hamzi\Vaultic\Events\PasskeyRenamed;

class PasskeyRenameTest extends TestCase
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

        Schema::create('admins', function (Blueprint $table) {
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
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function test_it_renames_an_owned_passkey_via_web_request(): void
    {
        Event::fake([PasskeyRenamed::class]);

        $user = TestUser::query()->create([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);

        $passkey = Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Old Name',
            'credential_id' => 'cred-rename-web',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('vaultic.passkeys.rename', $passkey), ['name' => 'New Name'])
            ->assertRedirect();

        $passkey->refresh();
        $this->assertSame('New Name', $passkey->name);

        Event::assertDispatched(PasskeyRenamed::class, function (PasskeyRenamed $event) use ($passkey) {
            return $event->passkey->id === $passkey->id
                && $event->oldName === 'Old Name'
                && $event->newName === 'New Name';
        });
    }

    public function test_it_renames_an_owned_passkey_via_ajax_request(): void
    {
        Event::fake([PasskeyRenamed::class]);

        $user = TestUser::query()->create([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);

        $passkey = Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Old Name',
            'credential_id' => 'cred-rename-ajax',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        $this->actingAs($user)
            ->patchJson(route('vaultic.passkeys.rename', $passkey), ['name' => 'New Name JSON'])
            ->assertOk()
            ->assertJson([
                'message' => 'Passkey renamed successfully.',
                'name' => 'New Name JSON',
            ]);

        $passkey->refresh();
        $this->assertSame('New Name JSON', $passkey->name);

        Event::assertDispatched(PasskeyRenamed::class);
    }

    public function test_it_cannot_rename_a_passkey_owned_by_another_user(): void
    {
        $user = TestUser::query()->create([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);

        $admin = AdminUser::query()->create([
            'email' => 'admin@example.com',
            'name' => 'Admin User',
        ]);

        $passkey = Passkey::query()->create([
            'authenticatable_type' => AdminUser::class,
            'authenticatable_id' => (string) $admin->getAuthIdentifier(),
            'name' => 'Admin Passkey',
            'credential_id' => 'cred-admin',
            'public_key' => 'public-key',
            'sign_count' => 1,
        ]);

        $this->actingAs($user)
            ->patch(route('vaultic.passkeys.rename', $passkey), ['name' => 'Hack Attempt'])
            ->assertStatus(404);

        $passkey->refresh();
        $this->assertSame('Admin Passkey', $passkey->name);
    }
}
