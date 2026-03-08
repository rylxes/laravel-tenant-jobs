<?php

namespace TenantJobs\Support;

use Illuminate\Bus\PendingBatch;
use TenantJobs\Contracts\TenantResolver;

class BatchContextPropagator
{
    /**
     * Wrap a PendingBatch so that then/catch/finally callbacks
     * execute within the correct tenant context.
     *
     * Usage:
     *   $batch = Bus::batch($jobs);
     *   BatchContextPropagator::wrap($batch)
     *       ->then(function (Batch $batch) { ... })
     *       ->dispatch();
     */
    public static function wrap(PendingBatch $batch): TenantAwarePendingBatch
    {
        $resolver = app(TenantResolver::class);
        $tenantId = $resolver->getCurrentTenantId();

        return new TenantAwarePendingBatch($batch, $tenantId);
    }
}
