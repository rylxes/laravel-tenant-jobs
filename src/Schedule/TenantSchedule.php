<?php

namespace TenantJobs\Schedule;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use TenantJobs\Contracts\TenantResolver;

class TenantSchedule
{
    public function __construct(
        protected Schedule $schedule,
        protected TenantResolver $resolver
    ) {}

    /**
     * Register a job to be dispatched for every tenant on the given schedule.
     *
     * Usage in app/Console/Kernel.php:
     *   $tenantSchedule = app(TenantSchedule::class);
     *   $tenantSchedule->job(new GenerateReport(), 'default')
     *       ->dailyAt('02:00');
     */
    public function job(
        object|string $job,
        ?string $queue = null,
        ?string $connection = null
    ): Event {
        $stagger = config('tenant-jobs.schedule_stagger_seconds', 1);

        return $this->schedule->call(function () use ($job, $queue, $connection, $stagger) {
            $delay = 0;

            foreach ($this->resolver->getAllTenantIds() as $tenantId) {
                $this->resolver->runAsTenant($tenantId, function () use ($job, $queue, $connection, $delay) {
                    $jobInstance = is_string($job) ? new $job() : clone $job;

                    if (property_exists($jobInstance, 'tenantId')) {
                        $jobInstance->tenantId = $this->resolver->getCurrentTenantId();
                    }

                    $pending = dispatch($jobInstance);

                    if ($queue) {
                        $pending->onQueue($queue);
                    }
                    if ($connection) {
                        $pending->onConnection($connection);
                    }
                    if ($delay > 0) {
                        $pending->delay(now()->addSeconds($delay));
                    }
                });

                $delay += $stagger;
            }
        });
    }

    /**
     * Run a callback for every tenant on the given schedule.
     * Useful for non-queued operations that need to happen in each tenant's context.
     */
    public function call(callable $callback): Event
    {
        return $this->schedule->call(function () use ($callback) {
            foreach ($this->resolver->getAllTenantIds() as $tenantId) {
                $this->resolver->runAsTenant($tenantId, $callback);
            }
        });
    }

    /**
     * Run an artisan command for every tenant.
     */
    public function command(string $command, array $parameters = []): Event
    {
        $stagger = config('tenant-jobs.schedule_stagger_seconds', 1);

        return $this->schedule->call(function () use ($command, $parameters, $stagger) {
            $delay = 0;

            foreach ($this->resolver->getAllTenantIds() as $tenantId) {
                if ($delay > 0) {
                    usleep($delay * 1_000_000);
                }

                $this->resolver->runAsTenant($tenantId, function () use ($command, $parameters) {
                    Artisan::call($command, $parameters);
                });

                $delay += $stagger;
            }
        });
    }
}
