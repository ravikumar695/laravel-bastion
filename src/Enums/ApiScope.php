<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Enums;

enum ApiScope: string
{
    // User scopes
    case USER_READ = 'users:read';
    case USER_WRITE = 'users:write';
    case USER_DELETE = 'users:delete';

    // Payment scopes
    case PAYMENT_READ = 'payments:read';
    case PAYMENT_CREATE = 'payments:create';
    case PAYMENT_REFUND = 'payments:refund';

    // Webhook scopes
    case WEBHOOK_READ = 'webhooks:read';
    case WEBHOOK_WRITE = 'webhooks:write';

    // Admin scope
    case ADMIN = '*';

    public function description(): string
    {
        return match ($this) {
            self::USER_READ => 'View user information',
            self::USER_WRITE => 'Create and update users',
            self::USER_DELETE => 'Delete users',
            self::PAYMENT_READ => 'View payment information',
            self::PAYMENT_CREATE => 'Create new payments',
            self::PAYMENT_REFUND => 'Process refunds',
            self::WEBHOOK_READ => 'View webhook configurations',
            self::WEBHOOK_WRITE => 'Create and update webhooks',
            self::ADMIN => 'Full API access',
        };
    }

    public function category(): string
    {
        return explode(':', $this->value)[0];
    }
}
