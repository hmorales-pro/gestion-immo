<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\PropertySlot;

/** A prospect cannot hold two active visits at overlapping times, whatever the property. */
class EnsureProspectIsFree
{
    /**
     * @throws BookingException
     */
    public function execute(PropertySlot $slot, string $email, ?Booking $except = null): void
    {
        $overlaps = Booking::query()
            ->whereIn('status', BookingStatus::active())
            ->where('prospect_email', $email)
            ->when($except, fn ($q) => $q->whereKeyNot($except->getKey()))
            ->whereHas('slot', fn ($q) => $q->overlapping($slot->start_time, $slot->end_time))
            ->exists();

        if ($overlaps) {
            throw BookingException::overlapping();
        }
    }
}
