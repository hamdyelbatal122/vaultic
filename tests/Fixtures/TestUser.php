<?php

namespace Hamzi\Vaultic\Tests\Fixtures;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;

class TestUser extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}
