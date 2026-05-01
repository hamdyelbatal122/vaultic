<?php

namespace Hamzi\Vaultic\Tests\Unit;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Schema\Blueprint;
use Hamzi\Vaultic\Concerns\HasPasskeys;
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
    }

    public function test_it_defines_a_morph_many_relationship()
    {
        $model = new class extends \Illuminate\Database\Eloquent\Model {
            use HasPasskeys;

            protected $table = 'users';

            protected $guarded = [];
        };

        $this->assertInstanceOf(MorphMany::class, $model->passkeys());
    }
}