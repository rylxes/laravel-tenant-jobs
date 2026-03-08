<?php

namespace TenantJobs\Tests\Stubs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use TenantJobs\Concerns\RunsCentrally;

class TestCentralJob implements ShouldQueue, RunsCentrally
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public static ?string $handledWithTenantId = null;
    public static bool $handled = false;

    public function handle(): void
    {
        static::$handled = true;
        static::$handledWithTenantId = app(\TenantJobs\Contracts\TenantResolver::class)->getCurrentTenantId();
    }

    public static function reset(): void
    {
        static::$handled = false;
        static::$handledWithTenantId = null;
    }
}
