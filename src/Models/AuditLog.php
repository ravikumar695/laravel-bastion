<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Models;

use Illuminate\Database\Eloquent\{Builder, Model, Relations\BelongsTo};
use Illuminate\Foundation\Auth\User;
use JustSteveKing\Bastion\Enums\TokenEnvironment;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'bastion_audit_logs';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'bastion_token_id',
        'action',
        'resource_type',
        'resource_id',
        'method',
        'endpoint',
        'status_code',
        'ip_address',
        'user_agent',
        'environment',
        'changes',
        'metadata',
        'response_time_ms',
        'error_message',
    ];

    /**
     * @var array<string, string|class-string>
     */
    protected $casts = [
        'environment' => TokenEnvironment::class,
        'changes' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /** @phpstan-ignore-next-line */
    public function user(): BelongsTo
    {
        return $this->belongsTo(
            related: User::class,
            foreignKey: 'user_id',
        );
    }

    /** @phpstan-ignore-next-line */
    public function token(): BelongsTo
    {
        return $this->belongsTo(
            related: BastionToken::class,
            foreignKey: 'bastion_token_id',
        );
    }

    // Query scopes
    /**
     * @param Builder<self> $query
     * @param int $userId
     * @return Builder<self>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * @param Builder<self> $query
     * @param string $type
     * @param int $id
     * @return Builder<self>
     */
    public function scopeForResource(Builder $query, string $type, int $id): Builder
    {
        return $query->where('resource_type', $type)
            ->where('resource_id', $id);
    }

    /**
     * @param Builder<self> $query
     * @param string $action
     * @return Builder<self>
     */
    public function scopeAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }

    /**
     * @param Builder<self> $query
     * @param TokenEnvironment|string $env
     * @return Builder<self>
     */
    public function scopeEnvironment(Builder $query, TokenEnvironment|string $env): Builder
    {
        $value = $env instanceof TokenEnvironment ? $env : TokenEnvironment::from($env);
        return $query->where('environment', $value);
    }

    /**
     * @param Builder<self> $query
     * @param int $days
     * @return Builder<self>
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeErrors(Builder $query): Builder
    {
        return $query->where('status_code', '>=', 400);
    }

    /**
     * @param Builder<self> $query
     * @return Builder<self>
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status_code', '<', 400);
    }
}
