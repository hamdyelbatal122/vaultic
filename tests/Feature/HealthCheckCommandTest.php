<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Tests\Feature;

use Hamzi\Vaultic\Tests\Fixtures\TestUser;
use Hamzi\Vaultic\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HealthCheckCommandTest extends TestCase
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

        config([
            'vaultic.rp.id' => 'localhost',
            'vaultic.rp.name' => 'Vaultic App',
            'vaultic.user_model' => TestUser::class,
            'app.url' => 'http://localhost',
        ]);
    }

    public function test_health_check_passes_with_valid_configuration(): void
    {
        $this->artisan('vaultic:health')
            ->expectsOutputToContain('Running Vaultic diagnostic health checks...')
            ->expectsOutputToContain('All health checks passed successfully.')
            ->assertExitCode(0);
    }

    public function test_health_check_fails_when_rp_id_is_missing(): void
    {
        config(['vaultic.rp.id' => '']);

        $this->artisan('vaultic:health')
            ->expectsOutputToContain('RP ID or RP Name is not set in vaultic config.')
            ->expectsOutputToContain('One or more health checks failed.')
            ->assertExitCode(1);
    }

    public function test_health_check_fails_when_passkeys_table_is_missing(): void
    {
        Schema::dropIfExists('passkeys');

        $this->artisan('vaultic:health')
            ->expectsOutputToContain('The [passkeys] table is missing.')
            ->expectsOutputToContain('One or more health checks failed.')
            ->assertExitCode(1);
    }

    public function test_health_check_fails_when_user_model_is_invalid(): void
    {
        config(['vaultic.user_model' => 'NonExistent\\Model\\Class']);

        $this->artisan('vaultic:health')
            ->expectsOutputToContain('does not exist.')
            ->expectsOutputToContain('One or more health checks failed.')
            ->assertExitCode(1);
    }
}
