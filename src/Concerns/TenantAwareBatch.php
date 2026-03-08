<?php

namespace TenantJobs\Concerns;

use Illuminate\Support\Facades\Bus;
use TenantJobs\Support\BatchContextPropagator;
use TenantJobs\Support\TenantAwarePendingBatch;

/**
 * Convenience trait for jobs or services that dispatch tenant-aware batches.
 *
 * Usage:
 *   class ProcessReports
 *   {
 *       use TenantAwareBatch;
 *
 *       public function handle()
 *       {
 *           $this->tenantBatch([
 *               new GenerateReport($month),
 *               new SendReportEmail($month),
 *           ])->then(function (Batch $batch) {
 *               // Runs in the correct tenant context
 *           })->dispatch();
 *       }
 *   }
 */
trait TenantAwareBatch
{
    protected function tenantBatch(array $jobs): TenantAwarePendingBatch
    {
        return BatchContextPropagator::wrap(Bus::batch($jobs));
    }
}
