<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Models;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, Relations\HasMany, SoftDeletes};
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Event;
use JustSteveKing\Bastion\Enums\{TokenEnvironment, TokenType};
use JustSteveKing\Bastion\Events\{TokenCreated, TokenExpired, TokenRevoked, TokenRotated};
use Random\RandomException;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $token_prefix
 * @property string $token_hash
 * @property TokenEnvironment $environment
 * @property TokenType $type
 * @property array<string>|null $scopes
 * @property array<string, mixed>|null $metadata
 * @property CarbonInterface|null $last_used_at
 * @property CarbonInterface|null $expires_at
 * @property CarbonInterface $created_at
 * @property CarbonInterface $updated_at
 * @property CarbonInterface|null $deleted_at
 * @property-read Authenticatable $user
 */
class BastionToken extends Model
{
    use SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'token_prefix',
        'token_hash',
        'environment',
        'type',
        'scopes',
        'metadata',
        'expires_at',
    ];

    /**
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'environment' => TokenEnvironment::class,
        'type' => TokenType::class,
        'scopes' => 'array',
        'metadata' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /** @var list<string> */
    protected $hidden = ['token_hash'];

    /**
     * @param Authenticatable $user
     * @param string $name
     * @param TokenEnvironment $environment
     * @param TokenType $type
     * @param array<string> $scopes
     * @return array<string, mixed>
     * @throws RandomException
     */
    public static function generate(
        Authenticatable $user,
        string $name,
        TokenEnvironment $environment = TokenEnvironment::Test,
        TokenType $type = TokenType::Restricted,
        array $scopes = [],
    ): array {
        $randomBytes = random_bytes(32);
        $tokenBody = base64_encode($randomBytes);
        $tokenBody = str_replace(['+', '/', '='], ['', '', ''], $tokenBody);

        // Create readable prefix
        $prefix = mb_substr($tokenBody, 0, 8);

        // Build full token
        $fullToken = sprintf(
            'app_%s_%s_%s',
            $environment->value,
            $type->prefix(),
            $tokenBody,
        );

        // Hash with HMAC using app key for additional security
        $appKey = config('app.key', '');
        if ( ! is_string($appKey)) {
            $appKey = '';
        }
        $hash = hash_hmac('sha256', $fullToken, $appKey);

        // Store hash only
        /** @var self $token */
        $token = static::query()->create([
            'user_id' => $user->getAuthIdentifier(),
            'name' => $name,
            'token_prefix' => $prefix,
            'token_hash' => $hash,
            'environment' => $environment,
            'type' => $type,
            'scopes' => $scopes,
        ]);

        Event::dispatch(new TokenCreated($token, $fullToken));

        return [
            'token' => $token,
            'plainTextToken' => $fullToken,
        ];
    }

    /**
     * @param string $plainTextToken
     * @return self|null
     */
    public static function findByToken(string $plainTextToken): ?self
    {
        // Hash the incoming token with HMAC
        $appKey = config('app.key', '');
        if ( ! is_string($appKey)) {
            $appKey = '';
        }
        $hash = hash_hmac('sha256', $plainTextToken, $appKey);

        // Direct lookup by hash (no timing attack possible here)
        return static::query()
            ->where('token_hash', $hash)
            ->first();
    }

    /**
     * Get the user that owns the token.
     *
     * @phpstan-ignore-next-line
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            related: User::class,
            foreignKey: 'user_id',
        );
    }

    /**
     * Get the audit logs for the token.
     *
     * @phpstan-ignore-next-line
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(
            related: AuditLog::class,
            foreignKey: 'bastion_token_id',
        );
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return false;
        }

        /** @var array<string> $scopes */
        $scopes = $this->scopes;

        foreach ($scopes as $tokenScope) {
            // Global wildcard - access to everything
            if ('*' === $tokenScope) {
                return true;
            }

            // Exact match
            if ($tokenScope === $scope) {
                return true;
            }

            // Check if token scope has a wildcard (e.g., "users:*")
            if (str_contains($tokenScope, '*')) {
                // Get the prefix (e.g., "users:" from "users:*")
                $prefix = str_replace('*', '', $tokenScope);

                // Check if the requested scope starts with this prefix
                if (str_starts_with($scope, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        if ($this->trashed()) {
            return false;
        }

        /** @var \Illuminate\Support\Carbon|null $expiresAt */
        $expiresAt = $this->expires_at;

        if ($expiresAt && $expiresAt->isPast()) {
            Event::dispatch(new TokenExpired($this));
            return false;
        }

        return true;
    }

    /**
     * @return void
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Revoke this token
     */
    public function revoke(?string $reason = null): bool
    {
        Event::dispatch(new TokenRevoked($this, $reason));
        return (bool) $this->delete();
    }

    /**
     * Rotate token - create new token and revoke old one
     *
     * @return array<string,mixed>
     * @throws RandomException
     */
    public function rotate(): array
    {
        $user = $this->user;

        $result = static::generate(
            user: $user,
            name: $this->name,
            environment: $this->environment,
            type: $this->type,
            scopes: $this->scopes ?? [],
        );

        // Copy metadata and expiry
        /** @var self $newToken */
        $newToken = $result['token'];

        /** @var string $plainTextToken */
        $plainTextToken = $result['plainTextToken'];

        $newToken->update([
            'expires_at' => $this->expires_at,
            'metadata' => array_merge(
                $this->metadata ?? [],
                ['rotated_from' => $this->id, 'rotated_at' => now()->toIso8601String()],
            ),
        ]);

        Event::dispatch(new TokenRotated($this, $newToken, $plainTextToken));

        // Revoke old token
        $this->revoke('Token rotated');

        return $result;
    }
}
