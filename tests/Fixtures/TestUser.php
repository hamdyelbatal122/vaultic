<?php

declare(strict_types=1);

namespace Hamzi\Vaultic\Tests\Fixtures;

use Hamzi\Vaultic\Concerns\HasPasskeys;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;

class TestUser extends Model implements AuthenticatableContract
{
    use Authenticatable;
    use HasPasskeys;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}
