<?php

namespace TenantJobs\Tests\Unit\Schedule;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Bus;
use TenantJobs\Schedule\TenantSchedule;
use TenantJobs\Tests\Stubs\TestJob;
use TenantJobs\Tests\TestCase;

class TenantScheduleTest extends TestCase
{
    public function test_job_dispatches_for_each_tenant(): void
    {
        Bus::fake();

        $schedule = $this->app->make(Schedule::class);
        $tenantSchedule = new TenantSchedule($schedule, $this->fakeTenantResolver);

        // Register the scheduled task
        $event = $tenantSchedule->job(TestJob::class);

        // Execute the scheduled callback
        $event->run($this->app);

        // Should have dispatched 3 jobs (one per tenant in FakeTenantResolver)
        Bus::assertDispatchedTimes(TestJob::class, 3);
    }

    public function test_call_runs_callback_for_each_tenant(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $tenantSchedule = new TenantSchedule($schedule, $this->fakeTenantResolver);

        $executedForTenants = [];

        $event = $tenantSchedule->call(function () use (&$executedForTenants) {
            $executedForTenants[] = $this->fakeTenantResolver->getCurrentTenantId();
        });

        $event->run($this->app);

        $this->assertEquals([1, 2, 3], $executedForTenants);
    }

    public function test_returns_schedule_event_for_chaining(): void
    {
        $schedule = $this->app->make(Schedule::class);
        $tenantSchedule = new TenantSchedule($schedule, $this->fakeTenantResolver);

        $event = $tenantSchedule->job(TestJob::class);

        $this->assertInstanceOf(\Illuminate\Console\Scheduling\Event::class, $event);
    }
}
