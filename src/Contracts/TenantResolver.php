<?php

namespace TenantJobs\Contracts;

interface TenantResolver
{
    /**
     * Get the ID of the current tenant, or null if no tenant is active.
     */
    public function getCurrentTenantId(): string|int|null;

    /**
     * Make the tenant with the given ID the current tenant.
     */
    public function setCurrentTenant(string|int $tenantId): void;

    /**
     * Forget/end the current tenant context entirely.
     */
    public function forgetCurrentTenant(): void;

    /**
     * Get all tenant IDs in the system (for scheduling).
     * Returns an iterable (generator-friendly for memory safety with large tenant sets).
     */
    public function getAllTenantIds(): iterable;

    /**
     * Execute a callable within a specific tenant's context, then restore previous state.
     */
    public function runAsTenant(string|int $tenantId, callable $callback): mixed;

    /**
     * Get the tenant ID from a queue job payload array.
     */
    public function getTenantIdFromPayload(array $payload): string|int|null;
}
