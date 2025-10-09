<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Enums;

enum TokenType: string
{
    case Public = 'public';
    case Secret = 'secret';
    case Restricted = 'restricted';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function prefix(): string
    {
        return match ($this) {
            self::Public => 'pk',
            self::Secret => 'sk',
            self::Restricted => 'rk',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Public Key',
            self::Secret => 'Secret Key',
            self::Restricted => 'Restricted Key',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Public => 'Limited access, safe for client-side use',
            self::Secret => 'Full access, must be kept secure',
            self::Restricted => 'Scoped access with specific permissions',
        };
    }

}
