<?php

namespace TenantJobs\Support;

use Illuminate\Support\Facades\Facade;

class FacadeResetter
{
    /**
     * Clear all configured facade instances and reset configured services.
     *
     * Called after every job to prevent tenant-specific singleton state
     * from leaking into the next job on the same worker.
     */
    public static function reset(): void
    {
        // 1. Clear specific facade resolved instances
        $facades = config('tenant-jobs.facades_to_clear', []);
        foreach ($facades as $accessor) {
            Facade::clearResolvedInstance($accessor);
        }

        // 2. Force the container to forget cached singletons for tenant-specific services
        $services = config('tenant-jobs.services_to_reset', []);
        foreach ($services as $abstract) {
            if (app()->resolved($abstract)) {
                app()->forgetInstance($abstract);
            }
        }

        // 3. Optionally purge DB connections to prevent cross-tenant connection reuse
        if (config('tenant-jobs.purge_db_connections', true)) {
            $connectionName = config('tenant-jobs.tenant_db_connection');
            if ($connectionName) {
                app('db')->purge($connectionName);
            }
        }
    }
}
