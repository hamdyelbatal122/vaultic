<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Schema\Blueprint;
use Hamzi\Vaultic\Concerns\HasPasskeys;
use Hamzi\Vaultic\Models\Passkey;
use Hamzi\Vaultic\Tests\TestCase;

class HasPasskeysTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
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

    private function getTestModel()
    {
        return new class extends \Illuminate\Database\Eloquent\Model {
            use HasPasskeys;

            protected $table = 'users';

            protected $guarded = [];
        };
    }

    public function test_it_defines_a_morph_many_relationship()
    {
        $model = $this->getTestModel();

        $this->assertInstanceOf(MorphMany::class, $model->passkeys());
    }

    public function test_it_checks_if_it_has_passkeys()
    {
        $model = $this->getTestModel();
        $model->id = 1;
        $model->exists = true;

        $this->assertFalse($model->hasPasskeys());

        Passkey::create([
            'authenticatable_type' => get_class($model),
            'authenticatable_id' => '1',
            'name' => 'My Passkey',
            'credential_id' => 'cred-1',
            'public_key' => 'key-1',
            'sign_count' => 0,
        ]);

        $this->assertTrue($model->hasPasskeys());
    }

    public function test_it_counts_passkeys()
    {
        $model = $this->getTestModel();
        $model->id = 1;
        $model->exists = true;

        $this->assertEquals(0, $model->passkeyCount());

        Passkey::create([
            'authenticatable_type' => get_class($model),
            'authenticatable_id' => '1',
            'name' => 'My Passkey 1',
            'credential_id' => 'cred-1',
            'public_key' => 'key-1',
            'sign_count' => 0,
        ]);

        $this->assertEquals(1, $model->passkeyCount());

        Passkey::create([
            'authenticatable_type' => get_class($model),
            'authenticatable_id' => '1',
            'name' => 'My Passkey 2',
            'credential_id' => 'cred-2',
            'public_key' => 'key-2',
            'sign_count' => 0,
        ]);

        $this->assertEquals(2, $model->passkeyCount());
    }

    public function test_it_gets_latest_passkey()
    {
        $model = $this->getTestModel();
        $model->id = 1;
        $model->exists = true;

        $this->assertNull($model->latestPasskey());

        $first = Passkey::create([
            'authenticatable_type' => get_class($model),
            'authenticatable_id' => '1',
            'name' => 'My Passkey 1',
            'credential_id' => 'cred-1',
            'public_key' => 'key-1',
            'sign_count' => 0,
            'last_used_at' => now()->subDay(),
            'created_at' => now()->subDay(),
        ]);

        $second = Passkey::create([
            'authenticatable_type' => get_class($model),
            'authenticatable_id' => '1',
            'name' => 'My Passkey 2',
            'credential_id' => 'cred-2',
            'public_key' => 'key-2',
            'sign_count' => 0,
            'last_used_at' => now(),
            'created_at' => now(),
        ]);

        $latest = $model->latestPasskey();
        $this->assertNotNull($latest);
        $this->assertEquals($second->id, $latest->id);
    }
}