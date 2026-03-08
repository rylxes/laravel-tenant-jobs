<?php

namespace TenantJobs\Concerns;

use TenantJobs\Contracts\TenantResolver;
use TenantJobs\Middleware\TenantJobMiddleware;

/**
 * Trait for Notification classes that implement ShouldQueue.
 *
 * Captures the current tenant ID at construction time and carries it
 * through serialization into the queued SendQueuedNotifications job.
 * Provides middleware that restores the tenant context when the
 * notification is processed by the queue worker.
 *
 * Usage:
 *   class InvoicePaid extends Notification implements ShouldQueue
 *   {
 *       use Queueable, TenantAwareNotification;
 *
 *       public function __construct()
 *       {
 *           $this->captureTenantId();
 *       }
 *   }
 */
trait TenantAwareNotification
{
    public string|int|null $tenantId = null;

    /**
     * Capture the current tenant ID.
     * Call this in your notification's constructor.
     */
    public function captureTenantId(): void
    {
        $this->tenantId = app(TenantResolver::class)->getCurrentTenantId();
    }

    /**
     * Provide middleware for the queued notification job.
     *
     * Laravel's NotificationSender merges notification middleware into
     * SendQueuedNotifications via ->through($middleware).
     */
    public function middleware(): array
    {
        return [
            app(TenantJobMiddleware::class),
        ];
    }
}
