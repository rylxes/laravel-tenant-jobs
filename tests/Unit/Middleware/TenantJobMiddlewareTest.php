<?php

namespace TenantJobs\Tests\Unit\Middleware;

use TenantJobs\Middleware\TenantJobMiddleware;
use TenantJobs\Tests\Stubs\FakeTenantResolver;
use TenantJobs\Tests\Stubs\TestCentralJob;
use TenantJobs\Tests\Stubs\TestJob;
use TenantJobs\Tests\TestCase;

class TenantJobMiddlewareTest extends TestCase
{
    private TenantJobMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new TenantJobMiddleware($this->fakeTenantResolver);
    }

    public function test_sets_tenant_from_job_property(): void
    {
        $job = new TestJob();
        $job->tenantId = 42;

        $executed = false;

        $this->middleware->handle($job, function ($job) use (&$executed) {
            $executed = true;
            $this->assertEquals(42, $this->fakeTenantResolver->getCurrentTenantId());

            return null;
        });

        $this->assertTrue($executed);
    }

    public function test_clears_tenant_after_job(): void
    {
        $job = new TestJob();
        $job->tenantId = 42;

        $this->middleware->handle($job, fn ($job) => null);

        $this->assertNull($this->fakeTenantResolver->getCurrentTenantId());
    }

    public function test_clears_tenant_after_job_throws_exception(): void
    {
        $job = new TestJob();
        $job->tenantId = 42;

        try {
            $this->middleware->handle($job, function ($job) {
                throw new \RuntimeException('Job failed');
            });
        } catch (\RuntimeException) {
            // Expected
        }

        $this->assertNull($this->fakeTenantResolver->getCurrentTenantId());
    }

    public function test_runs_centrally_marked_job_with_no_tenant(): void
    {
        // Pre-set a tenant to simulate a "dirty" worker
        $this->fakeTenantResolver->fakeSetCurrentTenant(99);

        $job = new TestCentralJob();
        $executed = false;

        $this->middleware->handle($job, function ($job) use (&$executed) {
            $executed = true;
            $this->assertNull($this->fakeTenantResolver->getCurrentTenantId());

            return null;
        });

        $this->assertTrue($executed);
    }

    public function test_job_without_tenant_id_runs_without_tenant_context(): void
    {
        $job = new TestJob();
        // tenantId is null by default

        $executed = false;

        $this->middleware->handle($job, function ($job) use (&$executed) {
            $executed = true;

            return null;
        });

        $this->assertTrue($executed);
        $this->assertNull($this->fakeTenantResolver->getCurrentTenantId());
    }

    public function test_restores_previous_tenant_if_different(): void
    {
        $this->fakeTenantResolver->fakeSetCurrentTenant(10);

        $job = new TestJob();
        $job->tenantId = 42;

        $this->middleware->handle($job, function ($job) {
            $this->assertEquals(42, $this->fakeTenantResolver->getCurrentTenantId());

            return null;
        });

        // Should restore the previous tenant
        $this->assertEquals(10, $this->fakeTenantResolver->getCurrentTenantId());
    }

    public function test_resolves_tenant_from_nested_notification(): void
    {
        $notification = new \stdClass();
        $notification->tenantId = 77;

        $job = new \stdClass();
        $job->notification = $notification;

        $executedWithTenantId = null;

        $this->middleware->handle($job, function ($job) use (&$executedWithTenantId) {
            $executedWithTenantId = $this->fakeTenantResolver->getCurrentTenantId();

            return null;
        });

        $this->assertEquals(77, $executedWithTenantId);
    }

    public function test_resolves_tenant_from_nested_mailable(): void
    {
        $mailable = new \stdClass();
        $mailable->tenantId = 88;

        $job = new \stdClass();
        $job->mailable = $mailable;

        $executedWithTenantId = null;

        $this->middleware->handle($job, function ($job) use (&$executedWithTenantId) {
            $executedWithTenantId = $this->fakeTenantResolver->getCurrentTenantId();

            return null;
        });

        $this->assertEquals(88, $executedWithTenantId);
    }

    public function test_resolves_tenant_from_method(): void
    {
        $job = new class {
            public function tenantId(): int
            {
                return 55;
            }
        };

        $executedWithTenantId = null;

        $this->middleware->handle($job, function ($job) use (&$executedWithTenantId) {
            $executedWithTenantId = $this->fakeTenantResolver->getCurrentTenantId();

            return null;
        });

        $this->assertEquals(55, $executedWithTenantId);
    }
}
