<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Tests;

use JustSteveKing\Bastion\Providers\PackageServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load Laravel's default migrations (includes users table)
        $this->loadLaravelMigrations();

        // Load package migrations
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            PackageServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'Bastion' => \JustSteveKing\Bastion\Facades\Bastion::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // Setup application key for HMAC hashing
        $app['config']->set('app.key', 'base64:' . base64_encode('testkeyfortesting1234567890123'));

        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        // Setup Bastion configuration
        $app['config']->set('bastion.user_model', User::class);
        $app['config']->set('bastion.errors.use_rfc7807', true);
        $app['config']->set('bastion.security.prevent_test_tokens_in_production', false);
        $app['config']->set('bastion.tables.tokens', 'bastion_tokens');
        $app['config']->set('bastion.tables.audit_logs', 'bastion_audit_logs');
        $app['config']->set('bastion.tables.webhooks', 'bastion_webhook_endpoints');
    }
}
