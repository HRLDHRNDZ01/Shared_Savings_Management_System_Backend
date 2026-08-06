<?php

namespace App\Observers;

use App\Events\NotificationCreated;
use App\Models\AppNotification;

class AppNotificationObserver
{
    public function created(AppNotification $notification): void
    {
        NotificationCreated::dispatch($notification);
    }
}
