<?php

namespace TenantJobs\Concerns;

/**
 * Marker interface for jobs that must explicitly run outside any tenant context.
 *
 * Unlike simply being "not tenant-aware" (which passively skips tenant
 * bootstrapping), implementing this interface causes TenantJobMiddleware to
 * actively call forgetCurrentTenant() before the job runs, ensuring a clean
 * central/landlord context even if the previous job on the same worker left
 * a tenant active.
 *
 * Usage:
 *   class PruneExpiredTokens implements ShouldQueue, RunsCentrally
 *   {
 *       use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
 *       // ...
 *   }
 */
interface RunsCentrally
{
    // Marker interface — no methods required
}
