<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JustSteveKing\Bastion\Bastion
 *
 * @method static array<string, mixed> generate(\Illuminate\Contracts\Auth\Authenticatable $user, string $name, \JustSteveKing\Bastion\Enums\TokenEnvironment $environment = \JustSteveKing\Bastion\Enums\TokenEnvironment::Test, \JustSteveKing\Bastion\Enums\TokenType $type = \JustSteveKing\Bastion\Enums\TokenType::Restricted, array $scopes = [])
 * @method static \JustSteveKing\Bastion\Models\BastionToken|null findToken(string $plainTextToken)
 * @method static bool revoke(\JustSteveKing\Bastion\Models\BastionToken $token)
 * @method static \Illuminate\Database\Eloquent\Collection<int, \JustSteveKing\Bastion\Models\BastionToken> tokensFor(\Illuminate\Contracts\Auth\Authenticatable $user)
 */
class Bastion extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \JustSteveKing\Bastion\Bastion::class;
    }
}
