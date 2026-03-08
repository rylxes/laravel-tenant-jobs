<?php

namespace TenantJobs\Tests\Unit\Support;

use Illuminate\Queue\Queue;
use TenantJobs\Support\PayloadStamper;
use TenantJobs\Tests\TestCase;

class PayloadStamperTest extends TestCase
{
    public function test_stamps_tenant_id_when_tenant_is_active(): void
    {
        $this->fakeTenantResolver->fakeSetCurrentTenant(42);

        // The service provider already registered a payload callback.
        // We can test via dispatching a job and checking the payload.
        // Instead, let's test the stamper directly by creating a new one
        // and using reflection to access the callbacks.
        $stamper = new PayloadStamper($this->fakeTenantResolver);
        $stamper->register();

        $callbacks = $this->getCreatePayloadCallbacks();
        $lastCallback = end($callbacks);

        $result = $lastCallback('database', 'default', ['job' => 'TestJob']);

        $this->assertEquals(['tenant_id' => 42], $result);
    }

    public function test_returns_empty_when_no_tenant_active(): void
    {
        $stamper = new PayloadStamper($this->fakeTenantResolver);
        $stamper->register();

        $callbacks = $this->getCreatePayloadCallbacks();
        $lastCallback = end($callbacks);

        $result = $lastCallback('database', 'default', ['job' => 'TestJob']);

        $this->assertEquals([], $result);
    }

    public function test_uses_configured_payload_key(): void
    {
        config(['tenant-jobs.payload_key' => 'custom_tenant']);

        $this->fakeTenantResolver->fakeSetCurrentTenant(99);

        $stamper = new PayloadStamper($this->fakeTenantResolver);
        $stamper->register();

        $callbacks = $this->getCreatePayloadCallbacks();
        $lastCallback = end($callbacks);

        $result = $lastCallback('database', 'default', ['job' => 'TestJob']);

        $this->assertEquals(['custom_tenant' => 99], $result);
    }

    protected function getCreatePayloadCallbacks(): array
    {
        $reflection = new \ReflectionClass(Queue::class);
        $property = $reflection->getProperty('createPayloadCallbacks');
        $property->setAccessible(true);

        return $property->getValue();
    }

    protected function tearDown(): void
    {
        // Clear the static callbacks to prevent test pollution
        $reflection = new \ReflectionClass(Queue::class);
        $property = $reflection->getProperty('createPayloadCallbacks');
        $property->setAccessible(true);
        $property->setValue([]);

        parent::tearDown();
    }
}
