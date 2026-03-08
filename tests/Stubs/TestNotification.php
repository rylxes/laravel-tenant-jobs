<?php

namespace TenantJobs\Tests\Stubs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use TenantJobs\Concerns\TenantAwareNotification;

class TestNotification extends Notification implements ShouldQueue
{
    use Queueable, TenantAwareNotification;

    public function __construct()
    {
        $this->captureTenantId();
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return ['message' => 'test'];
    }
}
