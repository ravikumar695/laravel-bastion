<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use JustSteveKing\Bastion\Bastion;
use JustSteveKing\Bastion\Commands\GenerateTokenCommand;
use JustSteveKing\Bastion\Commands\InstallCommand;
use JustSteveKing\Bastion\Commands\PruneAuditLogsCommand;
use JustSteveKing\Bastion\Commands\PruneTokensCommand;
use JustSteveKing\Bastion\Commands\RevokeTokenCommand;
use JustSteveKing\Bastion\Commands\RotateTokenCommand;
use JustSteveKing\Bastion\Http\Middleware\AuditApiRequest;
use JustSteveKing\Bastion\Http\Middleware\AuthenticateToken;

final class PackageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            path: __DIR__ . '/../../config/bastion.php',
            key: 'bastion',
        );

        $this->app->singleton(Bastion::class, fn($app) => new Bastion());

        // Register alias
        $this->app->alias(Bastion::class, 'bastion');
    }

    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/bastion.php' => config_path('bastion.php'),
        ], 'bastion-config');

        // Publish migrations
        $this->publishes([
            __DIR__ . '/../../database/migrations' => database_path('migrations'),
        ], 'bastion-migrations');

        // Load migrations (for testing)
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        // Register commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                GenerateTokenCommand::class,
                PruneAuditLogsCommand::class,
                PruneTokensCommand::class,
                RevokeTokenCommand::class,
                RotateTokenCommand::class,
            ]);
        }

        // Register middleware
        /** @var Router $router */
        $router = $this->app->make('router');
        $router->aliasMiddleware('bastion', AuthenticateToken::class);
        $router->aliasMiddleware('bastion.audit', AuditApiRequest::class);

        // Register routes
        $this->registerRoutes();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string|class-string>
     */
    public function provides(): array
    {
        return [
            Bastion::class,
            'bastion',
        ];
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        if ( ! config('bastion.routes.enabled', false)) {
            return;
        }

        Route::group([
            'prefix' => config('bastion.routes.prefix', 'bastion'),
            'middleware' => config('bastion.routes.middleware', ['api']),
        ], function (): void {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        });
    }
}
