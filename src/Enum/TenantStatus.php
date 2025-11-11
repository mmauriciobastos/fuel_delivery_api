<?php

declare(strict_types=1);

namespace App\Enum;

enum TenantStatus: string
{
    case ACTIVE = 'active';
    case SUSPENDED = 'suspended';
    case TRIAL = 'trial';

    /**
     * Get all available status values
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_map(fn(self $status) => $status->value, self::cases());
    }

    /**
     * Get human-readable label for the status
     */
    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::SUSPENDED => 'Suspended',
            self::TRIAL => 'Trial',
        };
    }

    /**
     * Check if tenant can perform operations
     */
    public function isOperational(): bool
    {
        return in_array($this, [self::ACTIVE, self::TRIAL], true);
    }
}
