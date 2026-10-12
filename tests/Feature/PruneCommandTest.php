<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Tests\Feature;

use Carbon\Carbon;
use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Tests\Fixtures\TestUser;
use Hamzi\Vaultic\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PruneCommandTest extends TestCase
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

    public function test_prune_requires_at_least_one_option(): void
    {
        $this->artisan('vaultic:prune')
            ->expectsOutputToContain('Please specify either --orphaned or --days=')
            ->assertExitCode(1);
    }

    public function test_prune_orphaned_removes_unlinked_passkeys(): void
    {
        $user = TestUser::query()->create([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Valid Device',
            'credential_id' => 'cred-valid',
            'public_key' => 'pk',
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => '99999', // orphaned
            'name' => 'Orphan Device',
            'credential_id' => 'cred-orphan',
            'public_key' => 'pk',
        ]);

        $this->artisan('vaultic:prune --orphaned')
            ->expectsOutputToContain('Orphaned passkeys pruned: 1')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('passkeys', ['credential_id' => 'cred-orphan']);
        $this->assertDatabaseHas('passkeys', ['credential_id' => 'cred-valid']);
    }

    public function test_prune_dry_run_does_not_delete(): void
    {
        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => '99999',
            'name' => 'Orphan Device',
            'credential_id' => 'cred-orphan-dry',
            'public_key' => 'pk',
        ]);

        $this->artisan('vaultic:prune --orphaned --dry-run')
            ->expectsOutputToContain('Executing in dry-run mode')
            ->expectsOutputToContain('Orphaned passkeys found: 1')
            ->assertExitCode(0);

        $this->assertDatabaseHas('passkeys', ['credential_id' => 'cred-orphan-dry']);
    }

    public function test_prune_inactive_removes_old_passkeys(): void
    {
        $user = TestUser::query()->create([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Old Device',
            'credential_id' => 'cred-old',
            'public_key' => 'pk',
            'last_used_at' => Carbon::now()->subDays(60),
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Active Device',
            'credential_id' => 'cred-recent',
            'public_key' => 'pk',
            'last_used_at' => Carbon::now()->subDays(5),
        ]);

        $this->artisan('vaultic:prune --days=30')
            ->expectsOutputToContain('Inactive passkeys (> 30 days) pruned: 1')
            ->assertExitCode(0);

        $this->assertDatabaseMissing('passkeys', ['credential_id' => 'cred-old']);
        $this->assertDatabaseHas('passkeys', ['credential_id' => 'cred-recent']);
    }
}
