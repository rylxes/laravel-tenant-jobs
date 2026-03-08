<?php

namespace TenantJobs\Resolvers;

use TenantJobs\Contracts\TenantResolver;

class SpatieTenantResolver implements TenantResolver
{
    public function getCurrentTenantId(): string|int|null
    {
        $tenantModel = config('multitenancy.tenant_model');

        return $tenantModel::current()?->getKey();
    }

    public function setCurrentTenant(string|int $tenantId): void
    {
        $tenantModel = config('multitenancy.tenant_model');
        $tenant = $tenantModel::find($tenantId);

        if ($tenant) {
            $tenant->makeCurrent();
        }
    }

    public function forgetCurrentTenant(): void
    {
        $tenantModel = config('multitenancy.tenant_model');

        if ($tenantModel::current()) {
            $tenantModel::forgetCurrent();
        }
    }

    public function getAllTenantIds(): iterable
    {
        $tenantModel = config('multitenancy.tenant_model');

        foreach ($tenantModel::cursor() as $tenant) {
            yield $tenant->getKey();
        }
    }

    public function runAsTenant(string|int $tenantId, callable $callback): mixed
    {
        $tenantModel = config('multitenancy.tenant_model');
        $tenant = $tenantModel::find($tenantId);

        return $tenant->execute($callback);
    }

    public function getTenantIdFromPayload(array $payload): string|int|null
    {
        $key = config('tenant-jobs.payload_key', 'tenant_id');

        return $payload[$key] ?? null;
    }
}
