<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\PropertySlot;
use App\Notifications\BookingCancelled;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

/** Cancels a pending or confirmed visit, frees the slot and removes the event from calendars. */
class CancelBooking
{
    /**
     * @throws BookingException
     */
    public function execute(Booking $booking, ?string $reason = null): Booking
    {
        DB::transaction(function () use ($booking, $reason): void {
            $locked = Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isActive()) {
                throw BookingException::alreadyDecided();
            }

            PropertySlot::query()->whereKey($locked->property_slot_id)->update(['is_booked' => false]);

            $locked->update([
                'status' => BookingStatus::Cancelled,
                'cancellation_reason' => filled($reason) ? trim($reason) : null,
                'decided_at' => now(),
                'sequence' => $locked->sequence + 1,
            ]);
        });

        $booking->refresh()->load(['property.agent', 'slot']);
        $property = $booking->property;
        $notification = new BookingCancelled($booking);

        $booking->notify($notification);
        $property->agent->notify($notification);
        Notification::route('mail', $property->owner_email)
            ->route('sms', $property->owner_phone)
            ->notify($notification);

        return $booking;
    }
}
