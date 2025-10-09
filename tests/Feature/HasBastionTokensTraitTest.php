<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use JustSteveKing\Bastion\Enums\{TokenEnvironment, TokenType};
use JustSteveKing\Bastion\Tests\{TestCase, User};

class HasBastionTokensTraitTest extends TestCase
{
    #[Test]
    public function user_can_create_token_via_trait(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $result = $user->createBastionToken(
            name: 'My Token',
            scopes: ['users:read'],
            environment: TokenEnvironment::Test,
            type: TokenType::Secret,
        );

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('plainTextToken', $result);
        $this->assertEquals('My Token', $result['token']->name);
    }

    #[Test]
    public function user_can_access_their_tokens(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $user->createBastionToken('Token 1');
        $user->createBastionToken('Token 2');
        $user->createBastionToken('Token 3');

        $this->assertCount(3, $user->bastionTokens);
    }
}
