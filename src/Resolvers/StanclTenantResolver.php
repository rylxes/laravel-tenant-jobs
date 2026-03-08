<?php

namespace TenantJobs\Resolvers;

use TenantJobs\Contracts\TenantResolver;

class StanclTenantResolver implements TenantResolver
{
    public function getCurrentTenantId(): string|int|null
    {
        return tenant()?->getTenantKey();
    }

    public function setCurrentTenant(string|int $tenantId): void
    {
        $tenantModel = config('tenancy.tenant_model');
        $tenant = $tenantModel::find($tenantId);

        if ($tenant) {
            tenancy()->initialize($tenant);
        }
    }

    public function forgetCurrentTenant(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }
    }

    public function getAllTenantIds(): iterable
    {
        $tenantModel = config('tenancy.tenant_model');

        foreach ($tenantModel::cursor() as $tenant) {
            yield $tenant->getTenantKey();
        }
    }

    public function runAsTenant(string|int $tenantId, callable $callback): mixed
    {
        $tenantModel = config('tenancy.tenant_model');
        $tenant = $tenantModel::find($tenantId);

        return $tenant->run($callback);
    }

    public function getTenantIdFromPayload(array $payload): string|int|null
    {
        $key = config('tenant-jobs.payload_key', 'tenant_id');

        return $payload[$key] ?? $payload['tenant_id'] ?? null;
    }
}
