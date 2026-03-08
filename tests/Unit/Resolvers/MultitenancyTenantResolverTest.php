<?php

namespace TenantJobs\Tests\Unit\Resolvers;

use TenantJobs\Resolvers\MultitenancyTenantResolver;
use TenantJobs\Tests\TestCase;

class MultitenancyTenantResolverTest extends TestCase
{
    public function test_get_tenant_id_from_payload_returns_value(): void
    {
        $resolver = new MultitenancyTenantResolver();

        $this->assertEquals(42, $resolver->getTenantIdFromPayload(['tenant_id' => 42]));
    }

    public function test_get_tenant_id_from_payload_returns_null_when_missing(): void
    {
        $resolver = new MultitenancyTenantResolver();

        $this->assertNull($resolver->getTenantIdFromPayload([]));
    }

    public function test_get_tenant_id_from_payload_uses_config_key(): void
    {
        config(['tenant-jobs.payload_key' => 'custom_tenant']);

        $resolver = new MultitenancyTenantResolver();

        $this->assertEquals(7, $resolver->getTenantIdFromPayload(['custom_tenant' => 7]));
        $this->assertNull($resolver->getTenantIdFromPayload(['tenant_id' => 7]));
    }
}
