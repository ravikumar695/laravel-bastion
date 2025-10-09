<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Tests\Feature;

use Illuminate\Support\Facades\Route;
use JustSteveKing\Bastion\Enums\{TokenEnvironment, TokenType};
use JustSteveKing\Bastion\Facades\Bastion;
use JustSteveKing\Bastion\Tests\{TestCase, User};
use PHPUnit\Framework\Attributes\Test;

class TokenAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Define test routes
        Route::middleware('bastion')->get('/protected', fn() => response()->json(['message' => 'success']));

        Route::middleware('bastion:users:read')->get('/scoped', fn() => response()->json(['message' => 'scoped success']));
    }

    #[Test]
    public function it_blocks_requests_without_token(): void
    {
        $response = $this->getJson('/protected');

        $response->assertStatus(401)
            ->assertJson([
                'title' => 'Unauthenticated',
                'detail' => 'API token required. Please provide a valid token via the Authorization header or api_key query parameter.',
                'status' => 401,
                'type' => 'https://bastion.laravel.com/errors/token_missing',
            ]);
    }

    #[Test]
    public function it_allows_requests_with_valid_token(): void
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
            type: TokenType::Restricted,
        );

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $result['plainTextToken'],
        )->getJson('/protected');

        $response->assertStatus(200)
            ->assertJson(['message' => 'success']);
    }

    #[Test]
    public function it_blocks_requests_with_invalid_token(): void
    {
        $response = $this->withHeader(
            'Authorization',
            'Bearer app_test_sk_invalidtoken123',
        )->getJson('/protected');

        $response->assertStatus(401)
            ->assertJson([
                'title' => 'Unauthenticated',
                'detail' => 'Invalid or expired API token',
                'status' => 401,
                'type' => 'https://bastion.laravel.com/errors/token_invalid',
            ]);
    }

    #[Test]
    public function it_validates_required_scopes(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        // Token without required scope
        $result = Bastion::generate(
            user: $user,
            name: 'Limited Token',
            environment: TokenEnvironment::Test,
            type: TokenType::Restricted,
            scopes: ['payments:read'],
        );

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $result['plainTextToken'],
        )->getJson('/scoped');

        $response->assertStatus(403)
            ->assertJson([
                'title' => 'Forbidden',
                'detail' => 'Missing required scope: users:read',
                'status' => 403,
                'type' => 'https://bastion.laravel.com/errors/insufficient_scope',
            ]);
    }

    #[Test]
    public function it_allows_requests_with_correct_scope(): void
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $result = Bastion::generate(
            user: $user,
            name: 'Scoped Token',
            environment: TokenEnvironment::Test,
            type: TokenType::Restricted,
            scopes: ['users:read'],
        );

        $response = $this->withHeader(
            'Authorization',
            'Bearer ' . $result['plainTextToken'],
        )->getJson('/scoped');

        $response->assertStatus(200)
            ->assertJson(['message' => 'scoped success']);
    }

    #[Test]
    public function it_accepts_token_from_query_parameter(): void
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
            type: TokenType::Restricted,
        );

        $response = $this->getJson(
            '/protected?api_key=' . $result['plainTextToken'],
        );

        $response->assertStatus(200);
    }
}
