<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Tests\Unit;

use Carbon\Carbon;
use Hamzi\Vaultic\Jobs\PruneOrphanedPasskeysJob;
use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Tests\Fixtures\TestUser;
use Hamzi\Vaultic\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PruneOrphanedPasskeysJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
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

    public function test_prune_orphaned_passkeys_job(): void
    {
        $user = TestUser::query()->create([
            'email' => 'user@example.com',
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Valid',
            'credential_id' => 'cred-valid-job',
            'public_key' => 'pk',
            'last_used_at' => Carbon::now(),
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => '99999',
            'name' => 'Orphan',
            'credential_id' => 'cred-orphan-job',
            'public_key' => 'pk',
            'last_used_at' => Carbon::now(),
        ]);

        Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Old Inactive',
            'credential_id' => 'cred-old-job',
            'public_key' => 'pk',
            'last_used_at' => Carbon::now()->subDays(100),
        ]);

        $job = new PruneOrphanedPasskeysJob(60);
        $job->handle();

        $this->assertDatabaseHas('passkeys', ['credential_id' => 'cred-valid-job']);
        $this->assertDatabaseMissing('passkeys', ['credential_id' => 'cred-orphan-job']);
        $this->assertDatabaseMissing('passkeys', ['credential_id' => 'cred-old-job']);
    }
}
