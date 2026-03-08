<?php

namespace TenantJobs\Support;

use Illuminate\Queue\Queue;
use TenantJobs\Contracts\TenantResolver;

class PayloadStamper
{
    public function __construct(
        protected TenantResolver $resolver
    ) {}

    /**
     * Register the payload callback with Laravel's Queue system.
     *
     * This stamps the current tenant ID into every dispatched job's payload
     * at the top level. The tenant_id survives in the failed_jobs table and
     * is readable on retry.
     */
    public function register(): void
    {
        $key = config('tenant-jobs.payload_key', 'tenant_id');

        Queue::createPayloadUsing(function (string $connection, ?string $queue, array $payload) use ($key) {
            $tenantId = $this->resolver->getCurrentTenantId();

            if ($tenantId === null) {
                return [];
            }

            return [$key => $tenantId];
        });
    }
}
