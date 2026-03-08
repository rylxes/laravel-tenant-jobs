<?php

namespace TenantJobs\Tests\Stubs;

use TenantJobs\Contracts\TenantResolver;

/**
 * In-memory tenant resolver for testing. Tracks all calls for assertions.
 */
class FakeTenantResolver implements TenantResolver
{
    protected string|int|null $currentTenantId = null;
    protected array $tenantIds = [];

    /** @var array<array{method: string, args: array}> */
    public array $calls = [];

    public function __construct(array $tenantIds = [])
    {
        $this->tenantIds = $tenantIds;
    }

    public function getCurrentTenantId(): string|int|null
    {
        $this->calls[] = ['method' => 'getCurrentTenantId', 'args' => []];

        return $this->currentTenantId;
    }

    public function setCurrentTenant(string|int $tenantId): void
    {
        $this->calls[] = ['method' => 'setCurrentTenant', 'args' => [$tenantId]];
        $this->currentTenantId = $tenantId;
    }

    public function forgetCurrentTenant(): void
    {
        $this->calls[] = ['method' => 'forgetCurrentTenant', 'args' => []];
        $this->currentTenantId = null;
    }

    public function getAllTenantIds(): iterable
    {
        $this->calls[] = ['method' => 'getAllTenantIds', 'args' => []];

        foreach ($this->tenantIds as $id) {
            yield $id;
        }
    }

    public function runAsTenant(string|int $tenantId, callable $callback): mixed
    {
        $this->calls[] = ['method' => 'runAsTenant', 'args' => [$tenantId]];

        $previous = $this->currentTenantId;
        $this->currentTenantId = $tenantId;

        try {
            return $callback();
        } finally {
            $this->currentTenantId = $previous;
        }
    }

    public function getTenantIdFromPayload(array $payload): string|int|null
    {
        $key = config('tenant-jobs.payload_key', 'tenant_id');

        return $payload[$key] ?? null;
    }

    /**
     * Pre-set the current tenant for testing setup.
     */
    public function fakeSetCurrentTenant(string|int|null $tenantId): void
    {
        $this->currentTenantId = $tenantId;
    }

    /**
     * Check if a specific method was called.
     */
    public function wasMethodCalled(string $method): bool
    {
        return collect($this->calls)->contains('method', $method);
    }

    /**
     * Get all calls to a specific method.
     */
    public function getCallsTo(string $method): array
    {
        return collect($this->calls)->where('method', $method)->values()->all();
    }
}
