<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Tests\Unit;

use Carbon\Carbon;
use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Tests\Fixtures\TestUser;
use Hamzi\Vaultic\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PasskeyModelTest extends TestCase
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

    public function test_passkey_is_used_and_days_since_last_use(): void
    {
        $user = TestUser::query()->create([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);

        $unused = Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Unused Key',
            'credential_id' => 'cred-unused',
            'public_key' => 'pk',
            'last_used_at' => null,
        ]);

        $used = Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Used Key',
            'credential_id' => 'cred-used',
            'public_key' => 'pk',
            'last_used_at' => Carbon::now()->subDays(5),
        ]);

        $this->assertFalse($unused->isUsed());
        $this->assertNull($unused->daysSinceLastUse());

        $this->assertTrue($used->isUsed());
        $this->assertSame(5, $used->daysSinceLastUse());
    }

    public function test_scopes_and_trait_passkey_helpers(): void
    {
        $user = TestUser::query()->create([
            'email' => 'user@example.com',
            'name' => 'Test User',
        ]);

        $p1 = Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Key 1',
            'credential_id' => 'cred-p1',
            'public_key' => 'pk',
            'aaguid' => '00000000-0000-0000-0000-000000000001',
            'last_used_at' => Carbon::now()->subDays(2),
        ]);

        $p2 = Passkey::query()->create([
            'authenticatable_type' => TestUser::class,
            'authenticatable_id' => (string) $user->getAuthIdentifier(),
            'name' => 'Key 2',
            'credential_id' => 'cred-p2',
            'public_key' => 'pk',
            'aaguid' => '00000000-0000-0000-0000-000000000002',
            'last_used_at' => null,
        ]);

        $this->assertSame(1, Passkey::query()->recent(10)->count());
        $this->assertSame(1, Passkey::query()->unused()->count());
        $this->assertSame(1, Passkey::query()->forAaguid('00000000-0000-0000-0000-000000000001')->count());

        $this->assertTrue($user->hasPasskey('cred-p1'));
        $this->assertFalse($user->hasPasskey('cred-nonexistent'));

        $found = $user->findPasskey('cred-p1');
        $this->assertNotNull($found);
        $this->assertSame('cred-p1', $found->credential_id);

        $this->assertTrue($user->deletePasskey('cred-p1'));
        $this->assertFalse($user->hasPasskey('cred-p1'));
    }
}
