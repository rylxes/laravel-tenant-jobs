<?php

namespace TenantJobs\Support;

use Illuminate\Queue\Events\JobRetryRequested;
use TenantJobs\Contracts\TenantResolver;

class RetryContextPreserver
{
    public function __construct(
        protected TenantResolver $resolver
    ) {}

    /**
     * Register the event listener for job retries.
     */
    public function register(): void
    {
        app('events')->listen(JobRetryRequested::class, [$this, 'handle']);
    }

    /**
     * When a failed job is retried, restore the tenant context.
     *
     * The tenant_id was stamped into the payload by PayloadStamper when the job
     * was originally dispatched. When it failed and was stored in failed_jobs,
     * the full JSON payload (including tenant_id) was preserved. When queue:retry
     * fires this event, we restore the tenant context so any deserialization
     * (e.g., model hydration with global scopes, retryUntil() checks) succeeds.
     */
    public function handle(JobRetryRequested $event): void
    {
        $payload = $event->payload();
        $key = config('tenant-jobs.payload_key', 'tenant_id');
        $tenantId = $payload[$key] ?? null;

        if ($tenantId !== null) {
            $this->resolver->setCurrentTenant($tenantId);
        }
    }
}
