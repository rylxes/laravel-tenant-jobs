<?php

namespace TenantJobs\Tests\Unit\Resolvers;

use InvalidArgumentException;
use TenantJobs\Contracts\TenantResolver;
use TenantJobs\Resolvers\ResolverFactory;
use TenantJobs\Tests\Stubs\FakeTenantResolver;
use TenantJobs\Tests\TestCase;

class ResolverFactoryTest extends TestCase
{
    public function test_makes_custom_resolver_from_fqcn(): void
    {
        $resolver = ResolverFactory::make(FakeTenantResolver::class);

        $this->assertInstanceOf(FakeTenantResolver::class, $resolver);
    }

    public function test_throws_exception_for_nonexistent_class(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');

        ResolverFactory::make('NonExistent\\Class\\Name');
    }

    public function test_throws_exception_for_class_not_implementing_interface(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement');

        ResolverFactory::make(\stdClass::class);
    }

    public function test_auto_detect_throws_when_no_package_installed(): void
    {
        // Neither stancl nor spatie is installed in test environment
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No supported tenancy package detected');

        ResolverFactory::detect();
    }
}
