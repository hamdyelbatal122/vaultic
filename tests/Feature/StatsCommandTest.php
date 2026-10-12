<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Tests\Feature;

use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Tests\Fixtures\TestUser;
use Hamzi\Vaultic\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class StatsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('passkeys', function (Blueprint $table): void {
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

    public function test_stats_command_outputs_passkey_metrics(): void
    {
        $user = TestUser::query()->create([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'MacBook Pro',
            'credential_id' => 'cred-stats-1',
            'public_key' => 'pk1',
            'sign_count' => 5,
            'transports' => ['internal', 'hybrid'],
            'last_used_at' => now(),
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'YubiKey',
            'credential_id' => 'cred-stats-2',
            'public_key' => 'pk2',
            'sign_count' => 0,
            'transports' => ['usb'],
            'last_used_at' => null,
        ]);

        $this->artisan('vaultic:stats')
            ->expectsOutputToContain('Vaultic Passkey Statistics')
            ->expectsOutputToContain('Total Registered Passkeys')
            ->expectsOutputToContain('Distinct Authenticatable Users')
            ->expectsOutputToContain('Authenticator Transports')
            ->assertExitCode(0);
    }

    public function test_stats_command_fails_if_table_missing(): void
    {
        Schema::dropIfExists('passkeys');

        $this->artisan('vaultic:stats')
            ->expectsOutputToContain('The [passkeys] table does not exist.')
            ->assertExitCode(1);
    }
}
