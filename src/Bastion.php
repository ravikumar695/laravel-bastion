<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion;

use Illuminate\Contracts\Auth\Authenticatable;
use JustSteveKing\Bastion\Enums\{TokenEnvironment, TokenType};
use JustSteveKing\Bastion\Models\BastionToken;

final class Bastion
{
    /**
     * Generate a new API token
     *
     * @param Authenticatable $user
     * @param string $name
     * @param TokenEnvironment $environment
     * @param TokenType $type
     * @param array<string> $scopes
     * @return array<string, mixed>
     */
    public function generate(
        Authenticatable $user,
        string $name,
        TokenEnvironment $environment = TokenEnvironment::Test,
        TokenType $type = TokenType::Restricted,
        array $scopes = [],
    ): array {
        return BastionToken::generate(
            user: $user,
            name: $name,
            environment: $environment,
            type: $type,
            scopes: $scopes,
        );
    }

    /**
     * Find token by plain text value
     */
    public function findToken(string $plainTextToken): ?BastionToken
    {
        return BastionToken::findByToken($plainTextToken);
    }

    /**
     * Revoke a token
     *
     * @param BastionToken $token
     * @return bool
     */
    public function revoke(BastionToken $token): bool
    {
        return (bool) $token->delete();
    }

    /**
     * Get all tokens for a user
     *
     * @param Authenticatable $user
     * @return \Illuminate\Database\Eloquent\Collection<int, BastionToken>
     */
    public function tokensFor(Authenticatable $user): \Illuminate\Database\Eloquent\Collection
    {
        return BastionToken::query()->where('user_id', $user->getAuthIdentifier())->get();
    }
}
