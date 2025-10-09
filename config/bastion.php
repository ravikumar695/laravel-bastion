<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Configure Bastion's built-in API routes.
    |
    */
    'routes' => [
        'enabled' => false,
        'prefix' => 'bastion',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Expiration
    |--------------------------------------------------------------------------
    |
    | Default expiration time for tokens (in days). Set to null for no expiration.
    |
    */
    'token_expiration_days' => null,

    /*
    |--------------------------------------------------------------------------
    | Audit Log Retention
    |--------------------------------------------------------------------------
    |
    | How long to keep audit logs (in days) before pruning.
    |
    */
    'audit_log_retention_days' => 90,

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Default rate limits per minute for different environments.
    |
    */
    'rate_limits' => [
        'test' => 100,
        'live' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for webhook deliveries.
    |
    */
    'webhooks' => [
        'timeout' => 30, // seconds
        'max_failures' => 10, // before auto-disable
        'retry_delay' => 60, // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration options.
    |
    */
    'security' => [
        'prevent_test_tokens_in_production' => true,
        'enable_audit_logging' => true,
        'enable_alerting' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Error Responses
    |--------------------------------------------------------------------------
    |
    | Configure error response format.
    |
    */
    'errors' => [
        'use_rfc7807' => true, // RFC 7807 Problem Details format
        // Base URL for error type identifiers
        // Final type will be base_url + code, e.g., https://bastion.laravel.com/errors/token_missing
        'base_url' => 'https://bastion.laravel.com/errors/',
    ],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model that tokens will be associated with.
    |
    */
    'user_model' => Illuminate\Foundation\Auth\User::class,

];
