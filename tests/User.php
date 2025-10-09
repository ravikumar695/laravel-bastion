<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Tests;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use JustSteveKing\Bastion\Concerns\HasBastionTokens;

class User extends Authenticatable
{
    use HasBastionTokens;
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): UserFactory
    {
        return new UserFactory();
    }
}
