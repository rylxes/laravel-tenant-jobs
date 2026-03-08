# Changelog

All notable changes to `laravel-tenant-jobs` will be documented in this file.

## [1.0.0] - 2026-03-08

### Added
- Initial release
- Automatic tenant context injection for queued jobs via middleware
- Support for both Spatie/laravel-multitenancy and Stancl/tenancy
- Auto-detection of installed tenancy package via ResolverFactory
- PayloadStamper for stamping tenant_id into job payloads
- FacadeResetter for clearing facade and singleton state between jobs
- RetryContextPreserver for restoring tenant context on job retry
- BatchContextPropagator for wrapping batch callbacks with tenant context
- TenantAwarePendingBatch decorator
- TenantSchedule for per-tenant scheduled job dispatch with stagger delay
- RunsCentrally marker interface for central-only jobs
- TenantAwareNotification trait for queued notifications
- TenantAwareBatch convenience trait
- Configurable auto-apply middleware via JobProcessing event
- Cleanup listeners on JobProcessed, JobFailed, JobExceptionOccurred
- Comprehensive unit test suite
- Laravel 10, 11, and 12 compatibility
