<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Tests\Unit;

use JustSteveKing\Bastion\Enums\{TokenEnvironment, TokenType};
use JustSteveKing\Bastion\Facades\Bastion;
use JustSteveKing\Bastion\Tests\{TestCase, User};
use PHPUnit\Framework\Attributes\Test;

final class TokenGenerationTest extends TestCase
{
    #[Test]
    public function it_generates_a_token_with_correct_format(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $result = Bastion::generate(
            user: $user,
            name: 'Test Token',
            environment: TokenEnvironment::Test,
            type: TokenType::Secret,
            scopes: ['users:read'],
        );

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('plainTextToken', $result);

        // Check token format: app_test_sk_...
        $this->assertStringStartsWith('app_test_sk_', $result['plainTextToken']);
    }

    #[Test]
    public function it_stores_hashed_token_in_database(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $result = Bastion::generate(
            user: $user,
            name: 'Test Token',
        );

        $token = $result['token'];

        // Plain text should not be in database
        $this->assertStringNotContainsString(
            $result['plainTextToken'],
            $token->token_hash,
        );

        // Should be a hash
        $this->assertEquals(64, mb_strlen($token->token_hash)); // SHA-256
    }

    #[Test]
    public function it_can_find_token_by_plain_text(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $result = Bastion::generate($user, 'Test Token');

        $foundToken = Bastion::findToken($result['plainTextToken']);

        $this->assertNotNull($foundToken);
        $this->assertEquals($result['token']->id, $foundToken->id);
    }

    #[Test]
    public function it_cannot_find_token_with_wrong_plain_text(): void
    {
        $foundToken = Bastion::findToken('app_test_sk_invalidtoken123');

        $this->assertNull($foundToken);
    }

    #[Test]
    public function it_stores_token_with_correct_properties(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $result = Bastion::generate(
            user: $user,
            name: 'My API Token',
            environment: TokenEnvironment::Live,
            type: TokenType::Restricted,
            scopes: ['users:read', 'payments:create'],
        );

        $token = $result['token'];

        $this->assertEquals('My API Token', $token->name);
        $this->assertEquals(TokenEnvironment::Live, $token->environment);
        $this->assertEquals(TokenType::Restricted, $token->type);
        $this->assertEquals(['users:read', 'payments:create'], $token->scopes);
        $this->assertEquals($user->id, $token->user_id);
    }
}
