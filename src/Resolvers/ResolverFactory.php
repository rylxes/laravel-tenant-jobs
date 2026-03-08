<?php

namespace TenantJobs\Resolvers;

use InvalidArgumentException;
use TenantJobs\Contracts\TenantResolver;

class ResolverFactory
{
    /**
     * Create a TenantResolver instance based on the given driver.
     */
    public static function make(string $driver = 'auto'): TenantResolver
    {
        if ($driver === 'auto') {
            $driver = static::detect();
        }

        return match ($driver) {
            'spatie' => new SpatieTenantResolver(),
            'stancl' => new StanclTenantResolver(),
            default => static::resolveCustom($driver),
        };
    }

    /**
     * Auto-detect which tenancy package is installed.
     */
    public static function detect(): string
    {
        if (class_exists(\Stancl\Tenancy\Tenancy::class)) {
            return 'stancl';
        }

        if (class_exists(\Spatie\Multitenancy\Multitenancy::class)) {
            return 'spatie';
        }

        throw new InvalidArgumentException(
            'No supported tenancy package detected. Install spatie/laravel-multitenancy '
            . 'or stancl/tenancy, or set a custom resolver class in config/tenant-jobs.php.'
        );
    }

    /**
     * Resolve a custom tenant resolver by fully-qualified class name.
     */
    protected static function resolveCustom(string $class): TenantResolver
    {
        if (! class_exists($class)) {
            throw new InvalidArgumentException(
                "Tenant resolver class [{$class}] does not exist."
            );
        }

        $instance = new $class();

        if (! $instance instanceof TenantResolver) {
            throw new InvalidArgumentException(
                "Class [{$class}] must implement " . TenantResolver::class
            );
        }

        return $instance;
    }
}
