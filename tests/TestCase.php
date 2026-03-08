<?php

namespace TenantJobs\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use TenantJobs\Contracts\TenantResolver;
use TenantJobs\Tests\Stubs\FakeTenantResolver;
use TenantJobs\TenantJobsServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected FakeTenantResolver $fakeTenantResolver;

    protected function setUp(): void
    {
        // Create the fake resolver before parent::setUp() boots the app
        $this->fakeTenantResolver = new FakeTenantResolver([1, 2, 3]);

        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [TenantJobsServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('tenant-jobs.resolver', FakeTenantResolver::class);
        $app['config']->set('tenant-jobs.auto_apply_middleware', true);
        $app['config']->set('tenant-jobs.payload_key', 'tenant_id');
        $app['config']->set('tenant-jobs.facades_to_clear', ['storage', 'log', 'mail', 'cache']);
        $app['config']->set('tenant-jobs.services_to_reset', []);
        $app['config']->set('tenant-jobs.purge_db_connections', false);
        $app['config']->set('tenant-jobs.schedule_stagger_seconds', 1);

        // Register the fake resolver singleton BEFORE service provider boots
        $resolver = $this->fakeTenantResolver;
        $app->singleton(TenantResolver::class, fn () => $resolver);
    }
}
