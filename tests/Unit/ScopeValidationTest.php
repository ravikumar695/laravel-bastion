<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Tests\Unit;

use JustSteveKing\Bastion\Facades\Bastion;
use JustSteveKing\Bastion\Tests\{TestCase, User};
use PHPUnit\Framework\Attributes\Test;

final class ScopeValidationTest extends TestCase
{
    #[Test]
    public function it_validates_exact_scope_match(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $result = Bastion::generate(
            user: $user,
            name: 'Test Token',
            scopes: ['users:read', 'users:write'],
        );

        $token = $result['token'];

        $this->assertTrue($token->hasScope('users:read'));
        $this->assertTrue($token->hasScope('users:write'));
        $this->assertFalse($token->hasScope('users:delete'));
    }

    #[Test]
    public function it_validates_wildcard_scopes(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $result = Bastion::generate(
            user: $user,
            name: 'Test Token',
            scopes: ['users:*'],
        );

        $token = $result['token'];

        $this->assertTrue($token->hasScope('users:read'));
        $this->assertTrue($token->hasScope('users:write'));
        $this->assertTrue($token->hasScope('users:delete'));
        $this->assertFalse($token->hasScope('payments:read'));
    }

    #[Test]
    public function it_validates_admin_scope(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $result = Bastion::generate(
            user: $user,
            name: 'Admin Token',
            scopes: ['*'],
        );

        $token = $result['token'];

        $this->assertTrue($token->hasScope('users:read'));
        $this->assertTrue($token->hasScope('payments:create'));
        $this->assertTrue($token->hasScope('anything:really'));
    }

    #[Test]
    public function it_returns_false_for_empty_scopes(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $result = Bastion::generate(
            user: $user,
            name: 'No Scope Token',
            scopes: [],
        );

        $token = $result['token'];

        $this->assertFalse($token->hasScope('users:read'));
    }
}
