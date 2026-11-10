<?php

namespace App\Actions\Bookings;

use App\Models\Booking;
use App\Notifications\InformationRequested;

class RequestInformation
{
    public function execute(Booking $booking, string $question): void
    {
        $booking->update(['information_requested_at' => now()]);
        $booking->loadMissing(['property.agent', 'slot']);

        $booking->notify(new InformationRequested($booking, trim($question)));
    }
}
