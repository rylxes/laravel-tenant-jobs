<?php

namespace TenantJobs\Tests\Unit\Support;

use Illuminate\Support\Facades\Facade;
use TenantJobs\Support\FacadeResetter;
use TenantJobs\Tests\TestCase;

class FacadeResetterTest extends TestCase
{
    public function test_clears_configured_facade_instances(): void
    {
        // Resolve Cache facade to populate the resolved instances cache
        // Access via the facade to ensure it's in the resolved instance cache
        \Illuminate\Support\Facades\Cache::getDefaultDriver();

        // Use reflection to verify the facade is cached
        $reflection = new \ReflectionClass(Facade::class);
        $prop = $reflection->getProperty('resolvedInstance');
        $prop->setAccessible(true);
        $resolved = $prop->getValue();

        $this->assertArrayHasKey('cache', $resolved);

        FacadeResetter::reset();

        // After reset, 'cache' should no longer be in the resolved cache
        $resolved = $prop->getValue();
        $this->assertArrayNotHasKey('cache', $resolved);
    }

    public function test_forgets_configured_service_instances(): void
    {
        config(['tenant-jobs.services_to_reset' => ['test.service']]);

        // Bind and resolve a service so it's cached
        $this->app->singleton('test.service', fn () => new \stdClass());
        $original = $this->app->make('test.service');

        $this->assertTrue($this->app->resolved('test.service'));

        FacadeResetter::reset();

        // The singleton binding still exists, but the cached instance was cleared
        // A new resolve should create a fresh instance
        $fresh = $this->app->make('test.service');
        $this->assertNotSame($original, $fresh);
    }

    public function test_does_not_crash_with_empty_config(): void
    {
        config([
            'tenant-jobs.facades_to_clear' => [],
            'tenant-jobs.services_to_reset' => [],
            'tenant-jobs.purge_db_connections' => false,
        ]);

        FacadeResetter::reset();

        $this->assertTrue(true);
    }
}
