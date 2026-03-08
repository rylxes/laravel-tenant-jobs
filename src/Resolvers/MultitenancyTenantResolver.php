<?php

namespace TenantJobs\Resolvers;

use TenantJobs\Contracts\TenantResolver;

class MultitenancyTenantResolver implements TenantResolver
{
    public function getCurrentTenantId(): string|int|null
    {
        return app(\Rylxes\Multitenancy\TenantManager::class)->current()?->getKey();
    }

    public function setCurrentTenant(string|int $tenantId): void
    {
        $tenantModel = config('multitenancy.tenant_model', \Rylxes\Multitenancy\Models\Tenant::class);
        $tenant = $tenantModel::find($tenantId);

        if ($tenant) {
            app(\Rylxes\Multitenancy\TenantManager::class)->makeCurrent($tenant);
        }
    }

    public function forgetCurrentTenant(): void
    {
        app(\Rylxes\Multitenancy\TenantManager::class)->forgetCurrent();
    }

    public function getAllTenantIds(): iterable
    {
        $tenantModel = config('multitenancy.tenant_model', \Rylxes\Multitenancy\Models\Tenant::class);

        foreach ($tenantModel::cursor() as $tenant) {
            yield $tenant->getKey();
        }
    }

    public function runAsTenant(string|int $tenantId, callable $callback): mixed
    {
        $tenantModel = config('multitenancy.tenant_model', \Rylxes\Multitenancy\Models\Tenant::class);
        $tenant = $tenantModel::find($tenantId);

        return app(\Rylxes\Multitenancy\TenantManager::class)->execute($tenant, $callback);
    }

    public function getTenantIdFromPayload(array $payload): string|int|null
    {
        $key = config('tenant-jobs.payload_key', 'tenant_id');

        return $payload[$key] ?? null;
    }
}
