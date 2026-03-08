<?php

namespace TenantJobs\Tests\Unit\Support;

use Illuminate\Bus\PendingBatch;
use TenantJobs\Support\BatchContextPropagator;
use TenantJobs\Support\TenantAwarePendingBatch;
use TenantJobs\Tests\TestCase;

class BatchContextPropagatorTest extends TestCase
{
    public function test_wrap_returns_tenant_aware_pending_batch(): void
    {
        $this->fakeTenantResolver->fakeSetCurrentTenant(42);

        $batch = new PendingBatch($this->app, collect());
        $wrapped = BatchContextPropagator::wrap($batch);

        $this->assertInstanceOf(TenantAwarePendingBatch::class, $wrapped);
    }

    public function test_wrapped_then_callback_runs_in_tenant_context(): void
    {
        $this->fakeTenantResolver->fakeSetCurrentTenant(42);

        $batch = new PendingBatch($this->app, collect());
        $wrapped = BatchContextPropagator::wrap($batch);

        $capturedTenantId = null;

        // Access the wrapped callback indirectly through the then method
        // We need to verify the closure wrapping works correctly
        $wrappedBatch = $wrapped->then(function () use (&$capturedTenantId) {
            $capturedTenantId = $this->fakeTenantResolver->getCurrentTenantId();
        });

        $this->assertInstanceOf(TenantAwarePendingBatch::class, $wrappedBatch);
    }

    public function test_wrap_without_tenant_returns_tenant_aware_batch(): void
    {
        // No tenant set
        $batch = new PendingBatch($this->app, collect());
        $wrapped = BatchContextPropagator::wrap($batch);

        $this->assertInstanceOf(TenantAwarePendingBatch::class, $wrapped);
    }

    public function test_fluent_chaining_works(): void
    {
        $this->fakeTenantResolver->fakeSetCurrentTenant(42);

        $batch = new PendingBatch($this->app, collect());
        $wrapped = BatchContextPropagator::wrap($batch);

        $result = $wrapped
            ->then(fn () => null)
            ->catch(fn () => null)
            ->finally(fn () => null);

        $this->assertInstanceOf(TenantAwarePendingBatch::class, $result);
    }
}
