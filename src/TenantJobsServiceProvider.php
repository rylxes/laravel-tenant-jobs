<?php

namespace TenantJobs;

use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobRetryRequested;
use Illuminate\Queue\Queue;
use Illuminate\Support\ServiceProvider;
use TenantJobs\Contracts\TenantResolver;
use TenantJobs\Middleware\TenantJobMiddleware;
use TenantJobs\Resolvers\ResolverFactory;
use TenantJobs\Schedule\TenantSchedule;
use TenantJobs\Support\FacadeResetter;

class TenantJobsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/tenant-jobs.php', 'tenant-jobs');

        $this->app->singleton(TenantResolver::class, function () {
            $driver = config('tenant-jobs.resolver', 'auto');

            return ResolverFactory::make($driver);
        });

        $this->app->singleton(TenantJobMiddleware::class, function ($app) {
            return new TenantJobMiddleware($app->make(TenantResolver::class));
        });

        $this->app->singleton(TenantSchedule::class, function ($app) {
            return new TenantSchedule(
                $app->make(\Illuminate\Console\Scheduling\Schedule::class),
                $app->make(TenantResolver::class)
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/tenant-jobs.php' => config_path('tenant-jobs.php'),
        ], 'tenant-jobs-config');

        // Stamp tenant_id into every dispatched job payload (lazy resolver)
        $this->registerPayloadStamper();

        // Restore tenant context on job retry
        $this->registerRetryPreserver();

        // Auto-apply tenant context via queue events
        if (config('tenant-jobs.auto_apply_middleware', true)) {
            $this->registerGlobalJobMiddleware();
        }

        // Safety-net cleanup after every job
        $this->registerCleanupListeners();
    }

    protected function registerPayloadStamper(): void
    {
        $key = config('tenant-jobs.payload_key', 'tenant_id');

        Queue::createPayloadUsing(function (string $connection, ?string $queue, array $payload) use ($key) {
            $tenantId = $this->app->make(TenantResolver::class)->getCurrentTenantId();

            if ($tenantId === null) {
                return [];
            }

            return [$key => $tenantId];
        });
    }

    protected function registerRetryPreserver(): void
    {
        $this->app['events']->listen(JobRetryRequested::class, function (JobRetryRequested $event) {
            $payload = $event->payload();
            $key = config('tenant-jobs.payload_key', 'tenant_id');
            $tenantId = $payload[$key] ?? null;

            if ($tenantId !== null) {
                $this->app->make(TenantResolver::class)->setCurrentTenant($tenantId);
            }
        });
    }

    /**
     * Listen to JobProcessing to initialize tenant context from the payload
     * before the job executes. This provides global coverage without requiring
     * each job to declare middleware explicitly.
     */
    protected function registerGlobalJobMiddleware(): void
    {
        $this->app['events']->listen(JobProcessing::class, function (JobProcessing $event) {
            $payload = $event->job->payload();
            $key = config('tenant-jobs.payload_key', 'tenant_id');
            $tenantId = $payload[$key] ?? null;

            if ($tenantId !== null) {
                $resolver = $this->app->make(TenantResolver::class);
                $resolver->setCurrentTenant($tenantId);
            }
        });
    }

    /**
     * Register cleanup listeners that fire after every job to guarantee
     * tenant context is cleared. This is a safety net — TenantJobMiddleware's
     * try/finally handles cleanup for explicitly-middlewared jobs, but these
     * listeners catch everything else.
     */
    protected function registerCleanupListeners(): void
    {
        $cleanup = function () {
            FacadeResetter::reset();

            try {
                $resolver = $this->app->make(TenantResolver::class);
                $resolver->forgetCurrentTenant();
            } catch (\Throwable) {
                // Swallow — cleanup failures must not mask real job errors
            }
        };

        $this->app['events']->listen(JobProcessed::class, fn () => $cleanup());
        $this->app['events']->listen(JobFailed::class, fn () => $cleanup());
        $this->app['events']->listen(JobExceptionOccurred::class, fn () => $cleanup());
    }
}
