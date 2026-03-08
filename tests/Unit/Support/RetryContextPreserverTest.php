<?php

namespace TenantJobs\Tests\Unit\Support;

use Illuminate\Queue\Events\JobRetryRequested;
use TenantJobs\Support\RetryContextPreserver;
use TenantJobs\Tests\TestCase;

class RetryContextPreserverTest extends TestCase
{
    protected function createRetryEvent(array $payload): JobRetryRequested
    {
        $job = new \stdClass();
        $job->payload = json_encode($payload);

        return new JobRetryRequested($job);
    }

    public function test_restores_tenant_on_retry_event(): void
    {
        $preserver = new RetryContextPreserver($this->fakeTenantResolver);

        $event = $this->createRetryEvent([
            'tenant_id' => 42,
            'job' => 'TestJob',
            'data' => [],
        ]);

        $preserver->handle($event);

        $this->assertEquals(42, $this->fakeTenantResolver->getCurrentTenantId());
    }

    public function test_does_nothing_when_no_tenant_in_payload(): void
    {
        $preserver = new RetryContextPreserver($this->fakeTenantResolver);

        $event = $this->createRetryEvent([
            'job' => 'TestJob',
            'data' => [],
        ]);

        $preserver->handle($event);

        $this->assertNull($this->fakeTenantResolver->getCurrentTenantId());
    }

    public function test_uses_configured_payload_key(): void
    {
        config(['tenant-jobs.payload_key' => 'org_id']);

        $preserver = new RetryContextPreserver($this->fakeTenantResolver);

        $event = $this->createRetryEvent([
            'org_id' => 99,
            'job' => 'TestJob',
            'data' => [],
        ]);

        $preserver->handle($event);

        $this->assertEquals(99, $this->fakeTenantResolver->getCurrentTenantId());
    }
}
