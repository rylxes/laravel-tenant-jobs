<?php

namespace TenantJobs\Middleware;

use Closure;
use TenantJobs\Concerns\RunsCentrally;
use TenantJobs\Contracts\TenantResolver;
use TenantJobs\Support\FacadeResetter;

class TenantJobMiddleware
{
    public function __construct(
        protected TenantResolver $resolver
    ) {}

    /**
     * Process the job within tenant context with guaranteed cleanup.
     */
    public function handle(mixed $job, Closure $next): mixed
    {
        // Jobs marked as RunsCentrally get an actively cleaned central context
        if ($job instanceof RunsCentrally) {
            $this->resolver->forgetCurrentTenant();
            FacadeResetter::reset();

            try {
                return $next($job);
            } finally {
                FacadeResetter::reset();
            }
        }

        // Resolve the tenant ID for this job
        $tenantId = $this->resolveTenantId($job);

        if ($tenantId === null) {
            // No tenant context — run as central with cleanup
            try {
                return $next($job);
            } finally {
                FacadeResetter::reset();
            }
        }

        // Initialize tenant context, run job, guaranteed cleanup
        $previousTenantId = $this->resolver->getCurrentTenantId();

        try {
            $this->resolver->setCurrentTenant($tenantId);

            return $next($job);
        } finally {
            FacadeResetter::reset();

            if ($previousTenantId !== null && $previousTenantId !== $tenantId) {
                $this->resolver->setCurrentTenant($previousTenantId);
            } else {
                $this->resolver->forgetCurrentTenant();
            }
        }
    }

    /**
     * Resolve the tenant ID from the job instance.
     *
     * Resolution order:
     * 1. Public $tenantId property on the job
     * 2. tenantId() method on the job
     * 3. Nested notification's tenantId (SendQueuedNotifications wraps the notification)
     * 4. Nested mailable's tenantId (SendQueuedMailable wraps the mailable)
     * 5. Current resolver context (fallback)
     */
    protected function resolveTenantId(mixed $job): string|int|null
    {
        if (property_exists($job, 'tenantId') && $job->tenantId !== null) {
            return $job->tenantId;
        }

        if (method_exists($job, 'tenantId')) {
            return $job->tenantId();
        }

        // SendQueuedNotifications wraps the notification object
        if (property_exists($job, 'notification')
            && is_object($job->notification)
            && property_exists($job->notification, 'tenantId')
        ) {
            return $job->notification->tenantId;
        }

        // SendQueuedMailable wraps the mailable object
        if (property_exists($job, 'mailable')
            && is_object($job->mailable)
            && property_exists($job->mailable, 'tenantId')
        ) {
            return $job->mailable->tenantId;
        }

        return $this->resolver->getCurrentTenantId();
    }
}
