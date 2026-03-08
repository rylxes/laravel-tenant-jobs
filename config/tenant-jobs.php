<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tenant Resolver
    |--------------------------------------------------------------------------
    |
    | Which tenancy package adapter to use. Supported values:
    |   - 'auto'          : Auto-detect installed tenancy package (recommended)
    |   - 'multitenancy'  : Force rylxes/laravel-multitenancy resolver (preferred)
    |   - 'spatie'         : Force spatie/laravel-multitenancy resolver
    |   - 'stancl'         : Force stancl/tenancy resolver
    |   - FQCN             : A custom class implementing TenantJobs\Contracts\TenantResolver
    |
    | Auto-detection order: rylxes/laravel-multitenancy > stancl/tenancy > spatie/laravel-multitenancy
    |
    */
    'resolver' => 'auto',

    /*
    |--------------------------------------------------------------------------
    | Auto-Apply Middleware
    |--------------------------------------------------------------------------
    |
    | When true, tenant context initialization and cleanup is applied to every
    | queued job automatically via JobProcessing/JobProcessed event listeners.
    | Disable this if you prefer to apply TenantJobMiddleware manually per-job.
    |
    */
    'auto_apply_middleware' => true,

    /*
    |--------------------------------------------------------------------------
    | Payload Key
    |--------------------------------------------------------------------------
    |
    | The key used to stamp the tenant ID into the queue job payload.
    | This value is stored at the top level of the JSON payload so it
    | survives in the failed_jobs table for retry context preservation.
    |
    */
    'payload_key' => 'tenant_id',

    /*
    |--------------------------------------------------------------------------
    | Facades to Clear
    |--------------------------------------------------------------------------
    |
    | Facade accessor names whose resolved instances should be cleared after
    | every job to prevent singleton state from leaking between tenants.
    | Keys are the facade accessor names (from getFacadeAccessor()).
    |
    */
    'facades_to_clear' => [
        'storage',
        'log',
        'mail',
        'cache',
    ],

    /*
    |--------------------------------------------------------------------------
    | Services to Reset
    |--------------------------------------------------------------------------
    |
    | Container abstract bindings to force-refresh after every job. These are
    | typically singletons that cache tenant-specific configuration and need
    | to be re-resolved when switching tenants.
    |
    */
    'services_to_reset' => [
        'filesystem.disk',
        'cache.store',
        'mailer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Purge Database Connections
    |--------------------------------------------------------------------------
    |
    | Whether to purge (disconnect and remove) database connections between
    | jobs to prevent cross-tenant connection reuse. Recommended for apps
    | using per-tenant database connections.
    |
    */
    'purge_db_connections' => true,

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Connection
    |--------------------------------------------------------------------------
    |
    | The name of the tenant database connection to purge. Set to null to
    | purge all non-default connections, or specify a connection name like
    | 'tenant' to purge only that specific connection.
    |
    */
    'tenant_db_connection' => null,

    /*
    |--------------------------------------------------------------------------
    | Schedule Stagger Seconds
    |--------------------------------------------------------------------------
    |
    | When using TenantSchedule to dispatch jobs for all tenants, this is the
    | delay (in seconds) added between each tenant's job dispatch to prevent
    | a thundering herd of simultaneous job processing.
    |
    */
    'schedule_stagger_seconds' => 1,

];
