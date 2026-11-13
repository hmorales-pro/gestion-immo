<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\PropertySlot;
use App\Notifications\BookingConfirmed;
use Illuminate\Support\Facades\DB;

class ApproveBooking
{
    /**
     * @param  bool  $notifyOwner  false when the owner made the decision themselves.
     *
     * @throws BookingException
     */
    public function execute(Booking $booking, bool $notifyOwner = true): Booking
    {
        DB::transaction(function () use ($booking): void {
            $booking = Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();
            $slot = PropertySlot::query()->whereKey($booking->property_slot_id)->lockForUpdate()->firstOrFail();

            if (! $booking->isPending()) {
                throw BookingException::alreadyDecided();
            }

            if ($slot->is_booked) {
                throw BookingException::slotUnavailable();
            }

            if ($slot->start_time->isPast()) {
                throw BookingException::slotInPast();
            }

            $booking->update(['status' => BookingStatus::Confirmed, 'decided_at' => now()]);
            $slot->update(['is_booked' => true]);
        });

        $booking->refresh()->load(['property.agent', 'slot']);
        $property = $booking->property;
        $notification = new BookingConfirmed($booking);

        $booking->notify($notification);
        $property->agent->notify($notification);

        if ($notifyOwner) {
            $property->ownerRoute()->notify($notification);
        }

        $property->occupantRoute()?->notify($notification);

        return $booking;
    }
}
