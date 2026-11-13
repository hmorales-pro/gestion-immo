<?php

namespace App\Actions\Bookings;

use App\Enums\BookingStatus;
use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Notifications\BookingRejected;
use Illuminate\Support\Facades\DB;

class RejectBooking
{
    /**
     * @throws BookingException
     */
    public function __construct(private readonly NotifyWaitlist $notifyWaitlist) {}

    public function execute(Booking $booking): Booking
    {
        DB::transaction(function () use ($booking): void {
            $locked = Booking::query()->whereKey($booking->getKey())->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw BookingException::alreadyDecided();
            }

            // Pending requests hold the slot; rejecting releases it for other prospects.
            $locked->update(['status' => BookingStatus::Rejected, 'decided_at' => now()]);
        });

        $booking->refresh()->load(['property.agent', 'slot']);
        $notification = new BookingRejected($booking);

        $booking->property->agent->notify($notification);
        $booking->notify($notification);

        $this->notifyWaitlist->execute($booking->property);

        return $booking;
    }
}
