<?php

namespace TenantJobs\Support;

use Closure;
use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
use TenantJobs\Contracts\TenantResolver;

class TenantAwarePendingBatch
{
    public function __construct(
        protected PendingBatch $batch,
        protected string|int|null $tenantId
    ) {}

    /**
     * Wrap a callback to execute within the captured tenant context.
     */
    protected function wrapCallback(callable $callback): Closure
    {
        $tenantId = $this->tenantId;

        if ($tenantId === null) {
            return Closure::fromCallable($callback);
        }

        return function () use ($callback, $tenantId) {
            $resolver = app(TenantResolver::class);
            $previousTenantId = $resolver->getCurrentTenantId();

            try {
                $resolver->setCurrentTenant($tenantId);

                return $callback(...func_get_args());
            } finally {
                FacadeResetter::reset();

                if ($previousTenantId !== null) {
                    $resolver->setCurrentTenant($previousTenantId);
                } else {
                    $resolver->forgetCurrentTenant();
                }
            }
        };
    }

    public function then(callable $callback): static
    {
        $this->batch->then($this->wrapCallback($callback));

        return $this;
    }

    public function catch(callable $callback): static
    {
        $this->batch->catch($this->wrapCallback($callback));

        return $this;
    }

    public function finally(callable $callback): static
    {
        $this->batch->finally($this->wrapCallback($callback));

        return $this;
    }

    public function progress(callable $callback): static
    {
        $this->batch->progress($this->wrapCallback($callback));

        return $this;
    }

    /**
     * Dispatch the underlying batch.
     */
    public function dispatch(): Batch
    {
        return $this->batch->dispatch();
    }

    /**
     * Proxy all other methods to the underlying PendingBatch.
     */
    public function __call(string $method, array $args): mixed
    {
        $result = $this->batch->{$method}(...$args);

        // If the underlying method returns the PendingBatch (fluent), return $this
        if ($result === $this->batch) {
            return $this;
        }

        return $result;
    }
}
