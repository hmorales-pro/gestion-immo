<?php

namespace App\Actions\Bookings;

use App\Models\Booking;
use App\Notifications\InformationProvided;
use Propaganistas\LaravelPhone\PhoneNumber;

class UpdateProspectInformation
{
    /**
     * @param  array{prospect_name: string, prospect_phone: string, employment_status: string, message?: string|null}  $data
     */
    public function execute(Booking $booking, array $data): Booking
    {
        $data['prospect_phone'] = (new PhoneNumber($data['prospect_phone'], 'FR'))->formatE164();

        $booking->update([...$data, 'information_requested_at' => null]);
        $booking->loadMissing(['property.agent', 'slot']);

        $booking->property->agent->notify(new InformationProvided($booking));

        return $booking;
    }
}
