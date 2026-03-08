# Laravel Tenant Jobs

> **[Full Documentation](https://rylxes.com/docs/laravel-tenant-jobs)** — Complete usage guide, configuration reference, and API docs.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/rylxes/laravel-tenant-jobs.svg?style=flat-square)](https://packagist.org/packages/rylxes/laravel-tenant-jobs)
[![Total Downloads](https://img.shields.io/packagist/dt/rylxes/laravel-tenant-jobs.svg?style=flat-square)](https://packagist.org/packages/rylxes/laravel-tenant-jobs)
[![License](https://img.shields.io/packagist/l/rylxes/laravel-tenant-jobs.svg?style=flat-square)](https://packagist.org/packages/rylxes/laravel-tenant-jobs)
[![PHP Version](https://img.shields.io/packagist/php-v/rylxes/laravel-tenant-jobs.svg?style=flat-square)](https://packagist.org/packages/rylxes/laravel-tenant-jobs)

Bulletproof multi-tenant queue job handling for Laravel. Fixes the six most common queue problems in multi-tenant applications.

**Recommended:** Use with [rylxes/laravel-multitenancy](https://github.com/rylxes/laravel-multitenancy) for zero-config integration. Also supports [stancl/tenancy](https://tenancyforlaravel.com/) and [spatie/laravel-multitenancy](https://spatie.be/docs/laravel-multitenancy).

## The Problem

Queue workers are long-running daemons. Unlike HTTP requests (which boot fresh per request), a queue worker boots once and processes hundreds of jobs sequentially. Any tenant state left over from one job bleeds into the next — database connections, cache prefixes, filesystem paths, facade singletons. All globally mutable in a single PHP process.

This package fixes all six known failure modes:

| # | Problem | What happens |
|---|---------|-------------|
| 1 | **Context leaking between jobs** | Tenant 1's DB connection stays active when Tenant 2's job runs |
| 2 | **Facade singletons not reset** | `Storage`, `Mail`, `Cache` facades retain the previous tenant's config |
| 3 | **Scheduled jobs have no tenant context** | Cron runs in central context — no way to dispatch per-tenant |
| 4 | **Failed job retry loses tenant context** | `queue:retry` doesn't restore the tenant |
| 5 | **Batch callbacks lose tenant context** | `then()`/`catch()`/`finally()` run in the wrong tenant |
| 6 | **Queued notifications silently don't run** | Tenant-aware notifications sit in the queue unprocessed |

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- One of: `rylxes/laravel-multitenancy` (^1.0, recommended), `spatie/laravel-multitenancy` (^3.0|^4.0), or `stancl/tenancy` (^3.0)

## Installation

```bash
composer require rylxes/laravel-tenant-jobs
```

The package auto-registers its service provider via Laravel's package discovery.

Publish the config file:

```bash
php artisan vendor:publish --tag=tenant-jobs-config
```

## Configuration

```php
// config/tenant-jobs.php

return [
    // 'auto' detects installed tenancy package.
    // Or force: 'multitenancy', 'spatie', 'stancl', or a custom FQCN.
    // Detection order: rylxes/laravel-multitenancy > stancl/tenancy > spatie/laravel-multitenancy
    'resolver' => 'auto',

    // Auto-apply tenant context to all queued jobs via event listeners.
    'auto_apply_middleware' => true,

    // Key used to stamp tenant ID into job payloads.
    'payload_key' => 'tenant_id',

    // Facade accessors to clear between jobs.
    'facades_to_clear' => ['storage', 'log', 'mail', 'cache'],

    // Container singletons to re-resolve between jobs.
    'services_to_reset' => ['filesystem.disk', 'cache.store', 'mailer'],

    // Purge DB connections between jobs (recommended for per-tenant DBs).
    'purge_db_connections' => true,

    // Specific connection to purge, or null for all non-default.
    'tenant_db_connection' => null,

    // Delay between per-tenant scheduled dispatches (prevents thundering herd).
    'schedule_stagger_seconds' => 1,
];
```

## How It Works

The package uses a two-layer defense:

1. **Event listeners** (`JobProcessing` / `JobProcessed` / `JobFailed`) provide global coverage — every job gets tenant context initialized from its payload and cleaned up after.
2. **Job middleware** (`TenantJobMiddleware`) wraps execution in `try/finally` for guaranteed cleanup even on exceptions.

A `TenantResolver` interface abstracts over all supported tenancy packages so the same code works with any of them.

## Usage

### Zero-config (auto mode)

With `auto_apply_middleware => true` (the default), **every queued job automatically gets tenant context**. If a job was dispatched while a tenant was active, the `PayloadStamper` stamps the `tenant_id` into the payload. When the worker picks it up, the event listener restores the tenant context. After the job finishes, everything is cleaned up.

You don't need to change your existing jobs.

### Problem 1 & 2: Context Leaking + Facade Singletons

**Handled automatically.** After every job, the package:
- Calls `forgetCurrentTenant()` on the resolver
- Clears configured facade instances (`Storage`, `Mail`, `Cache`, `Log`)
- Forgets container singletons for tenant-specific services
- Optionally purges database connections

### Problem 3: Scheduled Jobs Have No Tenant Context

Use `TenantSchedule` instead of Laravel's `Schedule::job()`:

```php
// app/Console/Kernel.php (Laravel 10)
// or bootstrap/app.php Schedule callback (Laravel 11+)

use TenantJobs\Schedule\TenantSchedule;

$tenantSchedule = app(TenantSchedule::class);

// Dispatches GenerateReport for EVERY tenant, with staggered delay
$tenantSchedule->job(new GenerateMonthlyReport())
    ->monthlyOn(1, '03:00');

// Run a callback in each tenant's context
$tenantSchedule->call(function () {
    // This runs once per tenant with the correct context
    CleanupExpiredData::dispatch();
})->daily();

// Run an artisan command per tenant
$tenantSchedule->command('tenant:cleanup')
    ->weeklyOn(1, '04:00');
```

At execution time, `TenantSchedule` iterates over all tenants, runs each job/callback within that tenant's context, and adds a configurable stagger delay to prevent thundering herd.

### Problem 4: Failed Job Retry Loses Tenant Context

**Handled automatically.** The `PayloadStamper` puts `tenant_id` at the top level of the JSON payload. When a job fails, this survives in the `failed_jobs.payload` column. When you run `queue:retry`, the package listens to `JobRetryRequested` and restores the tenant context.

```bash
# This just works — tenant context is restored automatically
php artisan queue:retry 5
php artisan queue:retry all
```

### Problem 5: Batch Callbacks Lose Tenant Context

Wrap your batch with `BatchContextPropagator`:

```php
use TenantJobs\Support\BatchContextPropagator;

// The tenant ID is captured at dispatch time and restored in callbacks
BatchContextPropagator::wrap(Bus::batch([
    new ProcessInvoice($invoice1),
    new ProcessInvoice($invoice2),
]))->then(function (Batch $batch) {
    // Runs in the correct tenant context
    Notification::send($admin, new BatchComplete());
})->catch(function (Batch $batch, Throwable $e) {
    // Also in the correct tenant context
    Log::error('Batch failed', ['batch' => $batch->id]);
})->dispatch();
```

Or use the convenience trait:

```php
use TenantJobs\Concerns\TenantAwareBatch;

class ProcessAllInvoices implements ShouldQueue
{
    use TenantAwareBatch;

    public function handle()
    {
        $this->tenantBatch([
            new ProcessInvoice($invoice1),
            new ProcessInvoice($invoice2),
        ])->then(function (Batch $batch) {
            // Correct tenant context guaranteed
        })->dispatch();
    }
}
```

### Problem 6: Queued Notifications Silently Don't Run

Add the `TenantAwareNotification` trait to your notification and call `captureTenantId()` in the constructor:

```php
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;
use TenantJobs\Concerns\TenantAwareNotification;

class InvoicePaid extends Notification implements ShouldQueue
{
    use Queueable, TenantAwareNotification;

    public function __construct(private Invoice $invoice)
    {
        $this->captureTenantId();
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    // ... your notification methods
}
```

The trait captures the tenant ID at construction time, and provides middleware that restores it when the queued `SendQueuedNotifications` job processes.

### Central Jobs (RunsCentrally)

For jobs that must run in the central/landlord context regardless of worker state, implement `RunsCentrally`:

```php
use TenantJobs\Concerns\RunsCentrally;

class PruneExpiredTokens implements ShouldQueue, RunsCentrally
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // Guaranteed clean central context —
        // even if the previous job left Tenant 5 active
    }
}
```

Unlike passively skipping tenant bootstrapping, `RunsCentrally` **actively** calls `forgetCurrentTenant()` before the job runs.

### Explicit Middleware (opt-in mode)

If you set `auto_apply_middleware => false` in config, you can apply the middleware per-job:

```php
use TenantJobs\Middleware\TenantJobMiddleware;

class ProcessOrder implements ShouldQueue
{
    public string|int|null $tenantId = null;

    public function middleware(): array
    {
        return [app(TenantJobMiddleware::class)];
    }
}
```

### Integration with rylxes/laravel-multitenancy

If you use [rylxes/laravel-multitenancy](https://github.com/rylxes/laravel-multitenancy), integration is **zero-config**:

```bash
composer require rylxes/laravel-multitenancy rylxes/laravel-tenant-jobs
```

The `ResolverFactory` auto-detects `rylxes/laravel-multitenancy` as the preferred resolver. No configuration needed — tenant context is automatically preserved across all queued jobs, retries, batches, and notifications.

### Custom Tenant Resolver

If you're not using a supported package, implement the `TenantResolver` interface:

```php
use TenantJobs\Contracts\TenantResolver;

class MyCustomResolver implements TenantResolver
{
    public function getCurrentTenantId(): string|int|null { /* ... */ }
    public function setCurrentTenant(string|int $tenantId): void { /* ... */ }
    public function forgetCurrentTenant(): void { /* ... */ }
    public function getAllTenantIds(): iterable { /* ... */ }
    public function runAsTenant(string|int $tenantId, callable $callback): mixed { /* ... */ }
    public function getTenantIdFromPayload(array $payload): string|int|null { /* ... */ }
}
```

Set it in config:

```php
'resolver' => App\Tenancy\MyCustomResolver::class,
```

## Architecture

```
src/
  TenantJobsServiceProvider.php       — Orchestrates all components
  Contracts/TenantResolver.php        — Abstraction over tenancy packages
  Resolvers/
    MultitenancyTenantResolver.php    — rylxes/laravel-multitenancy adapter (preferred)
    SpatieTenantResolver.php          — spatie/laravel-multitenancy adapter
    StanclTenantResolver.php          — stancl/tenancy adapter
    ResolverFactory.php               — Auto-detection + custom resolver
  Middleware/TenantJobMiddleware.php   — try/finally cleanup per-job
  Concerns/
    RunsCentrally.php                 — Marker interface for central jobs
    TenantAwareNotification.php       — Trait for queued notifications
    TenantAwareBatch.php              — Trait for batch dispatch
  Schedule/TenantSchedule.php         — Per-tenant scheduled dispatch
  Support/
    PayloadStamper.php                — Stamps tenant_id into payloads
    FacadeResetter.php                — Clears facade/singleton state
    RetryContextPreserver.php         — Restores tenant on retry
    BatchContextPropagator.php        — Wraps batch callbacks
    TenantAwarePendingBatch.php       — Decorated PendingBatch
```

## Testing

```bash
composer test
# or
./vendor/bin/phpunit
```

## License

MIT. See [LICENSE](LICENSE).
