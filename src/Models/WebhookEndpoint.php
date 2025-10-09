<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Models;

use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo};
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Str;
use JustSteveKing\Bastion\Enums\TokenEnvironment;

class WebhookEndpoint extends Model
{
    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'url',
        'events',
        'environment',
        'is_active',
    ];

    /**
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'environment' => TokenEnvironment::class,
        'events' => 'array',
        'is_active' => 'boolean',
        'last_success_at' => 'datetime',
        'disabled_at' => 'datetime',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = ['secret_hash'];

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public static function createEndpoint(array $attributes): array
    {
        // Generate webhook signing secret
        $secret = 'whsec_' . Str::random(32);
        $prefix = mb_substr($secret, 6, 8);

        /** @var self $endpoint */
        $endpoint = static::query()->create([
            ...$attributes,
            'secret_hash' => hash('sha256', $secret),
            'secret_prefix' => $prefix,
        ]);

        return [
            'endpoint' => $endpoint,
            'signingSecret' => $secret,
        ];
    }

    /**
     * Get the user that owns the webhook endpoint.
     *
     * @phpstan-ignore-next-line
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            related: User::class,
        );
    }

    /**
     * @param string $payload
     * @param string $signature
     * @param int $timestamp
     * @return bool
     */
    public function verifySignature(string $payload, string $signature, int $timestamp): bool
    {
        // Prevent replay attacks
        if (abs(time() - $timestamp) > 300) {
            return false;
        }

        $signedPayload = $timestamp . '.' . $payload;

        /** @var string $secretHash */
        $secretHash = $this->getAttribute('secret_hash');

        $expectedSignature = hash_hmac('sha256', $signedPayload, $secretHash);

        return hash_equals($expectedSignature, $signature);
    }

    public function recordSuccess(): void
    {
        $this->update([
            'failure_count' => 0,
            'last_success_at' => now(),
        ]);
    }

    public function recordFailure(): void
    {
        $this->increment('failure_count');

        /** @var int $failureCount */
        $failureCount = $this->getAttribute('failure_count');

        if ($failureCount >= config('bastion.webhooks.max_failures', 10)) {
            $this->update([
                'is_active' => false,
                'disabled_at' => now(),
            ]);
        }
    }
}
