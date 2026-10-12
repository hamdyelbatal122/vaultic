<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Tests\Unit;

use Hamzi\Vaultic\Contracts\PasskeyRepository;
use Hamzi\Vaultic\Jobs\RecordPasskeyUsageJob;
use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Tests\Fixtures\TestUser;
use Hamzi\Vaultic\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecordPasskeyUsageJobTest extends TestCase
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

    public function test_job_updates_passkey_sign_count_and_metadata(): void
    {
        $user = TestUser::query()->create([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);

        $passkey = Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Device',
            'credential_id' => 'cred-job',
            'public_key' => 'pk',
            'sign_count' => 1,
        ]);

        $job = new RecordPasskeyUsageJob('cred-job', 10, '198.51.100.42');
        $job->handle(app(PasskeyRepository::class));

        $passkey->refresh();

        $this->assertSame(10, $passkey->sign_count);
        $this->assertSame('198.51.100.42', $passkey->last_used_ip);
        $this->assertNotNull($passkey->last_used_at);
    }
}
