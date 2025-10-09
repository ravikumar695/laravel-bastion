<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use JustSteveKing\Bastion\Enums\{TokenEnvironment, TokenType};
use JustSteveKing\Bastion\Models\BastionToken;

/** @mixin Model */
trait HasBastionTokens
{
    public function bastionTokens(): HasMany
    {
        return $this->hasMany(
            related: BastionToken::class,
            foreignKey: 'user_id',
        );
    }

    public function createBastionToken(
        string $name,
        array $scopes = [],
        TokenEnvironment $environment = TokenEnvironment::Test,
        TokenType $type = TokenType::Restricted,
    ): array {
        return BastionToken::generate(
            user: $this,
            name: $name,
            environment: $environment,
            type: $type,
            scopes: $scopes,
        );
    }
}
