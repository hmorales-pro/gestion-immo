<?php

namespace App\Actions\Bookings;

use App\Models\Property;
use App\Models\WaitlistEntry;
use App\Notifications\WaitlistSlotAvailable;

/**
 * When a property has free slots again, tells everyone on its waiting list, once.
 * Entries are deleted right after: nothing is kept longer than needed.
 */
class NotifyWaitlist
{
    public function execute(Property $property): int
    {
        if (! $property->slots()->getQuery()->available()->exists()) {
            return 0;
        }

        $entries = $property->waitlist()->get();

        $entries->each(function (WaitlistEntry $entry) use ($property): void {
            $entry->notify(new WaitlistSlotAvailable($property, $entry->name));
            $entry->delete();
        });

        return $entries->count();
    }
}
