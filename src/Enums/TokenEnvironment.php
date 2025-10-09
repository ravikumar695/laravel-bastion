<?php

declare(strict_types=1);

namespace JustSteveKing\Bastion\Enums;

enum TokenEnvironment: string
{
    case Test = 'test';
    case Live = 'live';

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isProduction(): bool
    {
        return self::Live === $this;
    }

    public function label(): string
    {
        return match ($this) {
            self::Test => 'Test Environment',
            self::Live => 'Live Environment',
        };
    }
}
