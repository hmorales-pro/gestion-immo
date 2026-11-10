<?php

namespace App\Listeners;

use App\Models\Booking;
use App\Models\Property;
use App\Support\Demo;
use Illuminate\Notifications\Events\NotificationSending;

/**
 * In the public demo, nothing about demo data ever leaves the server:
 * visitors could otherwise type a stranger's e-mail or phone number.
 */
class BlockDemoNotifications
{
    public function handle(NotificationSending $event): bool
    {
        if (! Demo::enabled()) {
            return true;
        }

        $notification = $event->notification;
        $property = match (true) {
            property_exists($notification, 'booking') && $notification->booking instanceof Booking => $notification->booking->property,
            property_exists($notification, 'property') && $notification->property instanceof Property => $notification->property,
            default => null,
        };

        return ! Demo::ownsProperty($property);
    }
}
